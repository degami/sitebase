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
use DI\DependencyException;
use DI\NotFoundException;

/**
 * cart billing JSON
 */
class CartBilling extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/cart/{id:\d+}/billing';
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

        $cartsController = $this->containerMake(Carts::class);
        $form = $cartsController->getForm();

        $form->setAction($this->getUrl('admin.carts') . '?action=' . $this->getRequest()->get('action').'&cart_id='.$this->getRequest()->get('cart_id'));
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
            'html' => $form->render(),
            'js' => "",
        ];
    }
}
