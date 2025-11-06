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
use App\Base\Models\GiftCard;
use App\Base\Models\GiftcardRedeemCode;
use App\Base\Models\Order;
use App\Base\Models\OrderItem;
use App\Base\Models\OrderPayment;
use App\Base\Models\Website;
use Gplanchat\EventManager\Event;

class GiftcardsEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'order_paid' => [$this, 'sendGiftCards']
        ];
	}

    public function sendGiftCards(Event $e) 
    {
        /** @var Order */
        $order = $e->getData('object');

        /** @var OrderPayment */
        $payment = $e->getData('payment');

        foreach ($order->getItems() as $orderItem) {
            /** @var OrderItem $orderItem */
            if ($orderItem->getProduct() instanceof GiftCard) {
                try {
                    /** @var GiftcardRedeemCode $redeemCode */
                    $redeemCode = GiftcardRedeemCode::createFromOrderItem($orderItem)->persist();

                    App::getInstance()->getUtils()->queueTemplateMail(
                        App::getInstance()->getSiteData()->getConfigValue('commerce/emails/customer_care') ?? App::getInstance()->getSiteData()->getSiteEmail(),
                        $order->getBillingAddress()->getEmail(), 
                        App::getInstance()->getUtils()->translate('Here\'s your giftcard redeem code', locale: $order->getOwner()->getLocale()),
                        [
                            'redeem_code' => $redeemCode->getCode(),
                            'order' => [[Order::class, 'load'], ['id' => $order->getId()]], 
                            'payment' => [[OrderPayment::class, 'load'], ['id' => $payment->getId()]],
                            'website' => [[Website::class, 'load'], ['id' => $order->getWebsiteId()]]
                        ],
                        'commerce/giftcard_redeem_code',
                    );
                } catch (\Exception $e) {
                    App::getInstance()->getApplicationLogger()->exception($e);
                }
            }
        }
    }
}