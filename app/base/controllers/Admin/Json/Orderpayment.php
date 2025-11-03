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

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Controllers\Admin\Commerce\Orders;
use App\Base\Models\Order;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * order shipment JSON
 */
class Orderpayment extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/orders/{id:\d+}/payment';
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

        $order = Order::load($this->getRequest()->query->get('order_id'));

        $orderPayment = $order->getOrderPayment();

        $data = $orderPayment->getData();
        $data['additional_data'] = json_decode($orderPayment->getAdditionalData(), true);
        $html = (string) $this->getHtmlRenderer()->renderArrayOnTable($data);

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'html' => $html,
            'js' => "",
        ];
    }
}
