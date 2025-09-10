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
use Symfony\Component\HttpFoundation\Response;

class Add extends FrontendPageWithLang
{
    use CommercePageTrait;
    
    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && App::getInstance()->getEnv('ENABLE_COMMERCE', false);
    }

    /**
     * return route path
     *
     * @return array
     */
    public static function getRoutePath(): array
    {
        return [
            'frontend.commerce.cart.add' => '/commerce/cart/add/{product_details}', 
            'frontend.commerce.cart.add.withlang' => '/{lang:[a-z]{2}}/commerce/cart/add/{product_details}', 
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

        $productDetails = base64_decode(
            $this->getRouteData('product_details') ?? ''
        );

        if (isJson($productDetails)) {
            $productDetails = json_decode($productDetails, true);
            $productClass = $productDetails['class'] ?? null;
            $productId = $productDetails['id'] ?? null;

            $product = $this->containerCall([$productClass, 'load'], [$productId]);

            if (!$product) {
                $this->addErrorFlashMessage(
                    $this->getUtils()->translate('Product not found.')
                );

                if ($this->hasLang()) {
                    return $this->doRedirect($this->getUrl('frontend.commerce.cart.withlang', ['lang' => $this->getCurrentLocale()]));            
                }

                return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
            }

            $quantity = (int) $productDetails['quantity'] ?? 1;

            $this->getCart()->fullLoad()->addProduct(
                $product,
                $quantity
            );

            $this->getCart()->calculate()->persist();


            $this->addSuccessFlashMessage(
                $this->getUtils()->translate('Product added to cart successfully.')
            );

            if ($this->hasLang()) {
                return $this->doRedirect($this->getUrl('frontend.commerce.cart.withlang', ['lang' => $this->getCurrentLocale()]));            
            }

            return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
        }


        $this->addErrorFlashMessage(
            $this->getUtils()->translate('No product details provided.')
        );

        if ($this->hasLang()) {
            return $this->doRedirect($this->getUrl('frontend.commerce.cart.withlang', ['lang' => $this->getCurrentLocale()]));            
        }

        return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
    }
}