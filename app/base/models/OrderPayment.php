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

namespace App\Base\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;
use DateTime;

/**
 * Order Payment Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getOrderId()
 * @method int getUserId()
 * @method string getPaymentMethod()
 * @method string getTransactionId()
 * @method float getTransactionAmount()
 * @method string getCurrencyCode()
 * @method string getAdditionalData()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setOrderId(int $order_id)
 * @method self setUserId(int $user_id)
 * @method self setPaymentMethod(string $payment_method)
 * @method self setTransactionId(string $transaction_id)
 * @method self setTransactionAmount(float $transaction_amount)
 * @method self setCurrencyCode(string $currency_code)
 * @method self setAdditionalData(string $additional_data)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderPayment extends BaseModel
{
    use WithWebsiteTrait, WithOwnerTrait;

    protected ?Order $order = null;

    /**
     * Get the order associated with this payment
     *
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        if ($this->order) {
            return $this->order;
        }

        $order = Order::load($this->getOrderId());
        if (!$order) {
            return null;
        }

        return $this->setOrder($order)->order;
    }

    /**
     * Set the order for this payment
     *
     * @param Order $order
     * @return self
     */
    public function setOrder(Order $order): self
    {
        $this->order = $order;
        $this->setOrderId($order->getId());
        $this->setUserId($order->getUserId());
        $this->setWebsiteId($order->getWebsiteId());
        $this->setCurrencyCode($order->getCurrencyCode());

        return $this;
    }

    /**
     * Create a new OrderPayment instance for a given order
     *
     * @param Order $order
     * @param string $payment_method
     * @param string $transaction_id
     * @param mixed $additional_data
     * @return self
     */
    public static function createForOrder(Order $order, string $payment_method, string $transaction_id, $additional_data = null) : self
    {
        $payment = new self();
        $payment->setOrder($order);
        $payment->setPaymentMethod($payment_method);
        $payment->setTransactionAmount($order->getTotalInclTax());
        $payment->setTransactionId($transaction_id);
        if (!isJson($additional_data)) {
            $additional_data = json_encode($additional_data);
        }
        $payment->setAdditionalData($additional_data);

        return $payment;
    }
}
