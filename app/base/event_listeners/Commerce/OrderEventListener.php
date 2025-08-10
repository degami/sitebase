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
use App\Base\Models\OrderStatus;
use App\Base\Models\OrderStatusChange;
use Gplanchat\EventManager\Event;
use Exception;

class OrderEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'order_post_persist' => [$this, 'saveStatusChange']
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
}