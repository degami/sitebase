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

namespace App\Base\Controllers\Admin\Json;

use App\Base\Controllers\Admin\Commerce\Carts;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Models\Cart;
use App\Base\Models\CartDiscount;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * cart discounts JSON
 */
class CartDiscounts extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/cart/{id:\d+}/discounts';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_orders';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $route_data = $this->getRouteData();
        /** @var Cart $cart */
        $cart = $this->containerCall([Cart::class, 'load'], ['id' => $route_data['id']]);

        $discounts = array_map(
            function (CartDiscount $el) use ($cart) {
                return '<div class="items-elem">' .
                    ($el->getInitialDiscount()?->getCode() ?? '[no-name]') . ' ' . $this->getUtils()->formatPrice($el->getDiscountAmount(), $el->getCurrencyCode()) . ' ' .
                    ' <a class="deassoc_lnk" data-cart_id="' . $cart->id . '" data-discount_id="' . $el->id . '" href="' . $this->getUrl('crud.app.base.controllers.admin.json.cartdiscounts', ['id' => $cart->id]) . '?cart_id=' . $cart->id . '&discount_id=' . $el->id . '&action=remove_discount">&times;</a>' .
                    '</div>';
            },
            $cart->getDiscounts() ?? []
        );

        $discountsArr = array_map(
            function ($el) {
                return $el->getData();
            },
            $cart->getDiscounts() ?? []
        );

        $cartsController = $this->containerMake(Carts::class);
        $form = $cartsController->getForm();

        $form->setAction($this->getUrl('admin.commerce.carts') . '?action=' . $this->getRequest()->query->get('action').'&cart_id='.$this->getRequest()->query->get('cart_id'));
        $form->addField(
            'cart_id',
            [
                'type' => 'hidden',
                'default_value' => $cart->getId(),
            ]
        );

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'discounts' => $discountsArr,
            'html' => ($this->getRequest()->query->get('action') == 'new_discount' ? "<div class=\"items-list\">" . implode("", $discounts) . "</div><hr />" : '') . $form->render(),
            'js' => "",
        ];
    }
}
