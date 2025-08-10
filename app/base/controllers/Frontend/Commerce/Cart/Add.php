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
use App\Base\Abstracts\Controllers\FrontendPage;
use App\Base\Traits\CommercePageTrait;
use App\Base\Abstracts\Controllers\BasePage;
use Symfony\Component\HttpFoundation\Response;

class Add extends FrontendPage
{
    use CommercePageTrait;
    
    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::getInstance()->getEnv('ENABLE_COMMERCE', false);
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return '/commerce/cart/add/{product_details}';
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
            return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
        }


        $this->addErrorFlashMessage(
            $this->getUtils()->translate('No product details provided.')
        );
        return $this->doRedirect($this->getUrl('frontend.commerce.cart'));
    }
}