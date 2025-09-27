<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Controllers\Frontend\Commerce\Cart;

use App\App;
use App\Base\Traits\CommercePageTrait;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPageWithLang;
use App\Base\Models\CartDiscount;
use App\Base\Models\Discount as DiscountModel;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

class Discount extends FrontendPageWithLang
{
    use CommercePageTrait;
    
    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false);
    }

    /**
     * return route path
     *
     * @return array
     */
    public static function getRoutePath(): array
    {
        return [
            'frontend.commerce.cart.discount' => '/commerce/cart/discount/{action_details}',
            'frontend.commerce.cart.discount.withlang' => '/{lang:[a-z]{2}}/commerce/cart/discount/{action_details}',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return BasePage|Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function beforeRender() : BasePage|Response
    {
        // check if commerce is enabled
        if (!$this->hasLoggedUser()) {
            return $this->returnAfterLogin();
        }

        $actionDetails = base64_decode(
            $this->getRouteData('action_details') ?? ''
        );

        try {
            if (!isJson($actionDetails)) { 
                throw new RuntimeException($this->getUtils()->translate('No action details provided.'));
            }

            $actionDetails = json_decode($actionDetails, true);
            if (isset($actionDetails['from-request']) && $actionDetails['from-request'] === true) {
                $actionDetails = $this->getRequest()->query->all();
            }

            if (isset($actionDetails['action']) && $actionDetails['action'] === 'apply_discount') {
                $discountCode = $actionDetails['discount_code'] ?? '';
                if (empty($discountCode)) {
                    throw new RuntimeException($this->getUtils()->translate('Discount code is required.'));
                }

                $discount = DiscountModel::getCollection()
                    ->where([
                        'code' => $discountCode,
                        'active' => 1,
                        'website_id' => $this->getCurrentWebsite()->getId(),
                    ])
                    ->getFirst();

                if (!$discount) {
                    throw new RuntimeException($this->getUtils()->translate('Invalid or inactive discount code.'));
                }

                $this->getCart()->fullLoad();
                
                if (in_array($discount->getId(), array_map(fn($d) => $d->getInitialDiscountId(), $this->getCart()->getDiscounts() ?? []))) {
                    throw new RuntimeException($this->getUtils()->translate('Discount code already applied.'));
                }

                try {
                    // create a new CartDiscount instance
                    $cartDiscount = CartDiscount::createFromDiscount($discount, $this->getCart());

                    // save the cart discount
                    $cartDiscount->persist();

                    // recalculate the cart
                    $this->getCart()->calculate()->persist();

                    $this->addSuccessFlashMessage(
                        $this->getUtils()->translate('Discount applied successfully.')
                    );
                } catch (\Exception $e) {
                    throw new RuntimeException(
                        $this->getUtils()->translate('Failed to apply discount: ' . $e->getMessage())
                    );
                }              
            }

            if (isset($actionDetails['action']) && $actionDetails['action'] === 'remove_discount') {
                $discountId = $actionDetails['discount_id'] ?? null;
                if (!$discountId) {
                    throw new RuntimeException($this->getUtils()->translate('Discount ID is required to remove a discount.'));
                }

                $cartDiscount = CartDiscount::load($discountId);
                if (!$cartDiscount) {
                    throw new RuntimeException($this->getUtils()->translate('Discount not found.'));
                }
                if ($cartDiscount->getCartId() !== $this->getCart()->getId()) {
                    throw new RuntimeException($this->getUtils()->translate('Discount does not belong to this cart.'));
                }

                $cartDiscount->delete();
                $this->getCart()->calculate()->persist();
                $this->addSuccessFlashMessage(
                    $this->getUtils()->translate('Discount removed successfully.')
                );
            }


        } catch (\Exception $e) {
            $this->addErrorFlashMessage(
                $e->getMessage()
            );            
        }

        if ($this->hasLang()) {
            return $this->doRedirect($this->getUrl('frontend.commerce.cart.withlang', ['lang' => $this->getCurrentLocale()]));            
        }

        return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
    }
}