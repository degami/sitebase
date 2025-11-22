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

namespace App\Site\EventListeners\Commerce;

use App\Base\Interfaces\EventListenerInterface;
use App\Site\Models\DownloadableProduct;
use App\Base\Models\Order;
use App\Base\Models\OrderItem;
use Gplanchat\EventManager\Event;
use App\Site\Models\UserDownload;
use App\Base\Models\MediaElement;

class DownloadbleProductsEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'order_paid' => [$this, 'addUserDownloads']
        ];
	}

    public function addUserDownloads(Event $e) 
    {
        /** @var Order */
        $order = $e->getData('object');

        foreach ($order->getItems() as $orderItem) {
            /** @var OrderItem $orderItem */
            if ($orderItem->getProduct() instanceof DownloadableProduct) {
                /** @var DownloadableProduct $product */
                $product = $orderItem->getProduct();

                /** @var MediaElement $mediaElement */
                $mediaElement = $product->getMedia();

                $existingUserDownload = !empty(UserDownload::getCollection()->where([
                    'user_id' => $order->getUserId(),
                    'path' => $mediaElement->getPath(),
                ])->getItems());

                if (!$existingUserDownload) {
                    $userDownload = UserDownload::createByMediaElement($mediaElement);
                    $userDownload->setUserId($order->getUserId())->setDownloadAvailableCount(UserDownload::UNLIMITED_DOWNLOADS)->persist();
                }
            }
        }
    }
}