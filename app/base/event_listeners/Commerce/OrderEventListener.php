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

namespace App\Base\EventListeners\Commerce;

use App\App;
use App\Base\Interfaces\EventListenerInterface;
use App\Base\Models\Order;
use App\Base\Models\OrderPayment;
use App\Base\Models\OrderShipment;
use App\Base\Models\OrderStatus;
use App\Base\Models\OrderStatusChange;
use App\Base\Models\Website;
use Gplanchat\EventManager\Event;
use Exception;

class OrderEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'order_post_persist' => [$this, 'saveStatusChange'],
            'order_paid' => [$this, 'sendPaidNotification'],
            'order_shipment' => [$this, 'sendShipmentNotification']
        ];
	}

    public function saveStatusChange(Event $e) 
    {
        /** @var Order */
        $order = $e->getData('object');

        $changedData = $order->getChangedData();
        if (in_array('order_status_id', array_keys($changedData))) {
            /** @var OrderStatusChange $orderStatusChange */
            $orderStatusChange = App::getInstance()->containerMake(OrderStatusChange::class);
            $statusFrom = $changedData['order_status_id']['original'] ?? null;
            $statusTo = $changedData['order_status_id']['now'] ?? null;

            try {
                $statusFrom = OrderStatus::load($statusFrom)->getStatus();
            } catch (Exception $e) { $statusFrom = null; }

            try {
                $statusTo = OrderStatus::load($statusTo)->getStatus();
            } catch (Exception $e) { $statusTo = null; }

            $orderStatusChange->setOrder($order)->setStatusFrom($statusFrom)->setStatusTo($statusTo)->persist();
        }
   }

    public function sendPaidNotification(Event $e) 
    {
        /** @var Order */
        $order = $e->getData('object');

        /** @var OrderPayment */
        $payment = $e->getData('payment');

        try {
            App::getInstance()->getUtils()->queueTemplateMail(
                App::getInstance()->getSiteData()->getConfigValue('commerce/emails/customer_care') ?? App::getInstance()->getSiteData()->getSiteEmail(),
                $order->getBillingAddress()->getEmail(), 
                App::getInstance()->getUtils()->translate('Your order %s has been received', [$order->getOrderNumber()], locale: $order->getOwner()->getLocale()),
                [
                    'order' => [[Order::class, 'load'], ['id' => $order->getId()]], 
                    'payment' => [[OrderPayment::class, 'load'], ['id' => $payment->getId()]],
                    'website' => [[Website::class, 'load'], ['id' => $order->getWebsiteId()]]
                ],
                'commerce/new_order_customer'
            );
        } catch (\Exception $e) {
            App::getInstance()->getApplicationLogger()->exception($e);
        }

        try {
            App::getInstance()->getUtils()->queueInternalMail(
                App::getInstance()->getSiteData()->getSiteEmail(),
                App::getInstance()->getSiteData()->getConfigValue('commerce/emails/customer_care') ?? App::getInstance()->getSiteData()->getSiteEmail(),
                App::getInstance()->getUtils()->translate('New order incoming', locale: App::getInstance()->getSiteData()->getCurrentWebsite()?->getDefaultLocale()),
                App::getInstance()->getUtils()->translate('New order created: %s on website %s', [$order->getOrderNumber(), App::getInstance()->getSiteData()->getCurrentWebsite()?->getSiteName()], locale: App::getInstance()->getSiteData()->getCurrentWebsite()?->getDefaultLocale())
            );
        } catch (\Exception $e) {
            App::getInstance()->getApplicationLogger()->exception($e);
        }
   }

    public function sendShipmentNotification(Event $e) 
    {
        /** @var Order */
        $order = $e->getData('object');

        /** @var OrderShipment */
        $shipment = $e->getData('shipment');

        /** @var array */
        $items = $e->getData('items');

        try {
            App::getInstance()->getUtils()->queueTemplateMail(
                App::getInstance()->getSiteData()->getSiteEmail(),
                App::getInstance()->getSiteData()->getConfigValue('commerce/emails/customer_care') ?? App::getInstance()->getSiteData()->getSiteEmail(), 
                App::getInstance()->getUtils()->translate('New shipment for your order %s', [$order->getOrderNumber()], locale: $order->getOwner()->getLocale()),
                [
                    'order' => [[Order::class, 'load'], ['id' => $order->getId()]], 
                    'shipment' => [[OrderShipment::class, 'load'], ['id' => $shipment->getId()]],
                    'website' => [[Website::class, 'load'], ['id' => $order->getWebsiteId()]]
                ],
                'commerce/new_shipment_customer'
            );
        } catch (\Exception $e) {
            App::getInstance()->getApplicationLogger()->exception($e);
        }
   }
}