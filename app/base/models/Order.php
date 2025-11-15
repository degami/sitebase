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

use App\App;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;
use App\Base\Interfaces\Commerce\PaymentMethodInterface;

/**
 * Order Model
 *
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method int getCartId()
 * @method string getOrderNumber()
 * @method int getOrderStatusId()
 * @method float getDiscountAmount()
 * @method float getSubTotal()
 * @method float getTaxAmount()
 * @method string getShippingMethod()
 * @method float getShippingAmount()
 * @method float getTotalInclTax()
 * @method string getCurrencyCode()
 * @method float getAdminDiscountAmount()
 * @method float getAdminSubTotal()
 * @method float getAdminTaxAmount()
 * @method float getAdminShippingAmount()
 * @method float getAdminTotalInclTax()
 * @method string getAdminCurrencyCode()
 * @method string getAdditionalData()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setCartId(int $cart_id)
 * @method self setOrderNumber(string $order_number)
 * @method self setOrderStatusId(int $order_status_id)
 * @method self setDiscountAmount(float $discount_amount)
 * @method self setSubTotal(float $sub_total)
 * @method self setTaxAmount(float $tax_amount)
 * @method self setShippingMethod(string $shipping_method)
 * @method self setShippingAmount(float $shipping_amount)
 * @method self setTotalInclTax(float $total_incl_tax)
 * @method self setCurrencyCode(string $currency_code)
 * @method self setAdminDiscountAmount(float $admin_discount_amount)
 * @method self setAdminSubTotal(float $admin_sub_total)
 * @method self setAdminTaxAmount(float $admin_tax_amount)
 * @method self setAdminShippingAmount(float $admin_shipping_amount)
 * @method self setAdminTotalInclTax(float $admin_total_incl_tax)
 * @method self setAdminCurrencyCode(string $admin_currency_code)
 * @method self setAdditionalData(string $additional_data)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Order extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?OrderAddress $billingAddress = null;
    protected ?OrderAddress $shippingAddress = null;
    protected ?OrderStatus $orderStatus = null;
    protected ?OrderPayment $orderPayment = null;
    protected array $items = [];

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * Get the order items
     *
     * @return \App\Base\Models\OrderItem[]
     */
    public function getItems(): array
    {
        if ($this->items) {
            return $this->items;
        }

        if (!$this->getId()) {
            return [];
        }

        return $this->setItems(OrderItem::getCollection()->where(['order_id' => $this->getId()])->getItems())->items;
    }

    /**
     * Set the order items
     *
     * @param \App\Base\Models\OrderItem[] $items
     * @return self
     */
    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Get the order status
     *
     * @return OrderStatus|null
     */
    public function getOrderStatus(): ?OrderStatus
    {
        if (!$this->getOrderStatusId()) {
            return null;
        }

        return $this->setOrderStatus(OrderStatus::getCollection()->where(['id' => $this->getOrderStatusId()])->getFirst())->orderStatus;
    }

    /**
     * Get the billing address for this order
     *
     * @return OrderAddress|null
     */
    public function getBillingAddress() : ?OrderAddress
    {
        if ($this->billingAddress) {
            return $this->billingAddress;
        }
        
        if (!$this->getId()) {
            return null;
        }

        $address = OrderAddress::getCollection()->where(['order_id' => $this->getId(), 'type' => 'billing'])->getFirst();
        if (!$address) {
            return null;
        }

        return $this->setBillingAddress($address)->billingAddress;
    }

    /**
     * Get the shipping address for this order
     *
     * @return OrderAddress|null
     */
    public function getShippingAddress() : ?OrderAddress
    {
        if ($this->shippingAddress) {
            return $this->shippingAddress;
        }
        
        if (!$this->getId()) {
            return null;
        }

        $address = OrderAddress::getCollection()->where(['order_id' => $this->getId(), 'type' => 'shipping'])->getFirst();
        if (!$address) {
            return null;
        }

        return $this->setShippingAddress($address)->shippingAddress;
    }

    /**
     * Set the order status and update related properties
     *
     * @param OrderStatus $orderStatus
     * @return self
     */
    public function setOrderStatus(OrderStatus $orderStatus): self
    {
        $this->orderStatus = $orderStatus;
        $this->setOrderStatusId($orderStatus->getId());
        return $this;
    }

    /**
     * Set the billing and shipping addresses for this order
     *
     * @param OrderAddress|Address $address
     * @return self
     */
    public function setBillingAddress(OrderAddress|Address $address): self
    {
        if (!$address instanceof OrderAddress) {
            $address = OrderAddress::createFromAddress($address);
            if (!$address->getLongitude() && !$address->getLatitude()) {
                try {
                    $geocoder = App::getInstance()->getGeocoder();
                    $coords = $geocoder->geocode($address->getFullAddress());
                    if ($coords) {
                        $address->setLatitude($coords['lat'])->setLongitude($coords['lon']);
                    }
                } catch (\Exception $e) {}
            }
        }

        $this->billingAddress = $address->setType('billing')->setOrder($this);
        return $this;
    }

    /**
     * Set the shipping address for this order
     *
     * @param OrderAddress|Address $address
     * @return self
     */
    public function setShippingAddress(OrderAddress|Address $address): self
    {
        if (!$address instanceof OrderAddress) {
            $address = OrderAddress::createFromAddress($address);
            if (!$address->getLongitude() && !$address->getLatitude()) {
                try {
                    $geocoder = App::getInstance()->getGeocoder();
                    $coords = $geocoder->geocode($address->getFullAddress());
                    if ($coords) {
                        $address->setLatitude($coords['lat'])->setLongitude($coords['lon']);
                    }
                } catch (\Exception $e) {}
            }
        }

        $this->shippingAddress = $address->setType('shipping')->setOrder($this);
        return $this;
    }

    /**
     * Create an order from a cart
     *
     * @param Cart $cart
     * @return self
     */
    public static function createFromCart(Cart $cart): self
    {
        // ensure cart is calculated
        $cart->calculate();

        $order = new self();
        $order->setUserId($cart->getUserId())
            ->setWebsiteId($cart->getWebsiteId())
            ->setCartId($cart->getId())
            ->setCurrencyCode($cart->getCurrencyCode())
            ->setAdminCurrencyCode($cart->getAdminCurrencyCode())
            ->setSubTotal($cart->getSubTotal())
            ->setDiscountAmount($cart->getDiscountAmount())
            ->setTaxAmount($cart->getTaxAmount())
            ->setShippingMethod($cart->getShippingMethod())
            ->setShippingAmount($cart->getShippingAmount())
            ->setTotalInclTax($cart->getTotalInclTax())
            ->setAdminSubTotal($cart->getAdminSubTotal())
            ->setAdminDiscountAmount($cart->getAdminDiscountAmount())
            ->setAdminTaxAmount($cart->getAdminTaxAmount())
            ->setAdminShippingAmount($cart->getAdminShippingAmount())
            ->setAdminTotalInclTax($cart->getAdminTotalInclTax());

        foreach ($cart->getItems() as $item) {
            /** @var CartItem $item */
            /** @var OrderItem $orderItem */
            $orderItem = App::getInstance()->containerMake(OrderItem::class);
            $orderItem->setProduct($item->getProduct())
                ->setQuantity($item->getQuantity())
                ->setCartItemId($item->getId())
                ->setUnitPrice($item->getUnitPrice())
                ->setSubTotal($item->getSubTotal())
                ->setDiscountAmount($item->getDiscountAmount())
                ->setTaxAmount($item->getTaxAmount())
                ->setTotalInclTax($item->getTotalInclTax())
                ->setCurrencyCode($item->getCurrencyCode())
                ->setAdminUnitPrice($item->getAdminUnitPrice())
                ->setAdminSubTotal($item->getAdminSubTotal())
                ->setAdminDiscountAmount($item->getAdminDiscountAmount())
                ->setAdminTaxAmount($item->getAdminTaxAmount())
                ->setAdminTotalInclTax($item->getAdminTotalInclTax())
                ->setOrder($order);

            $order->items[] = $orderItem;
        }

        if ($cart->getShippingAddressId()) {
            $order->setShippingAddress(App::getInstance()->containerCall([Address::class, 'load'], ['id' => $cart->getShippingAddressId()]));
        }

        if ($cart->getBillingAddressId()) {
            $order->setBillingAddress(App::getInstance()->containerCall([Address::class, 'load'], ['id' => $cart->getBillingAddressId()]));
        }

        return $order;
    }

    public function postPersist(array $persistOptions = []): BaseModel
    {
        // propagate order_id to order items
        foreach ($this->getItems() as $item) {
            /** @var OrderItem $item */
            $item->setOrder($this)->persist();
        }

        // propagate order_id to order addresses
        if ($this->billingAddress) {
            $this->billingAddress->setOrder($this)->persist();
        }
        if ($this->shippingAddress) {
            $this->shippingAddress->setOrder($this)->persist();
        }

        // set order number if missing
        if ($this->getOrderNumber() == null) {
            // update object in memory
            $this->setOrderNumber(static::calcOrderNumber($this));

            // write to db
            App::getInstance()->getDb()->update('order', ['order_number' => $this->getOrderNumber()], ['id = ' . $this->getId()]);
        }

        return parent::postPersist($persistOptions);
    }

    /**
     * Calculate the order number based on the website ID and order ID
     *
     * @param Order $order
     * @return string
     */
    public static function calcOrderNumber(Order $order) : string
    {
        return "ORDER_".$order->getWebsiteId().str_pad($order->getId(), 10, '0', STR_PAD_LEFT);
    }

    /**
     * Pay the order using a payment method
     *
     * @param string $payment_method_name
     * @param string $transaction_id
     * @param mixed $additional_data
     * @return self
     */
    public function pay(string $payment_method_name, string $transaction_id, $additional_data = null) : self
    {
        $payment = OrderPayment::createForOrder($this, $payment_method_name, $transaction_id, $additional_data);
        $payment->persist();

        $this->setOrderPayment($payment);

        App::getInstance()->getUtils()->addQueueMessage('consolidate_stock', ['order_id' => $this->getId()]);

        App::getInstance()->event('order_paid', ['object' => $this, 'payment' => $payment]);

        return $this;
    }

    /**
     * Get the order payment
     *
     * @return OrderPayment|null
     */
    public function getOrderPayment() : ?OrderPayment
    {
        if ($this->orderPayment) {
            return $this->orderPayment;
        }

        $payment = OrderPayment::getCollection()->where(['order_id' => $this->getId()])->getFirst();
        if (!$payment) {
            return null;
        }

        return $this->setOrderPayment($payment)->orderPayment;
    }

    /**
     * Set the order payment
     *
     * @param OrderPayment $orderPayment
     * @return self
     */
    public function setOrderPayment(OrderPayment $orderPayment): self
    {
        $this->orderPayment = $orderPayment;
        return $this;
    }

    /**
     * Get the comments associated with this order
     *
     * @return BaseCollection|null
     */
    public function getComments() : ?BaseCollection
    {
        if (!$this->getId()) {
            return null;
        }

        return OrderComment::getCollection()->where(['order_id' => $this->getId()])->addOrder(['created_at' => 'DESC']);
    }

    /**
     * Check if order requires shipping
     *
     * @return bool
     */
    public function requiresShipping() : bool
    {
        foreach ($this->getItems() as $item) {
            /** @var OrderItem $item */
            if ($item->requireShipping()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ship an amount of order items using a shipment method
     *
     * @param string $shipping_method_name
     * @param string $shipment_code
     * @param array $items
     * @param mixed $additional_data
     * @return self
     */
    public function ship(string $shipping_method_name, string $shipment_code, ?array $items = null, $additional_data = null) : ?OrderShipment
    {
        if (!$this->requireShipping()) {
            return null;
        }

        // if no items are specified, all items are shipped
        if (is_null($items)) {
            $items = $this->getItems();
        }

        $shipment = OrderShipment::createForOrder($this, $shipping_method_name, $shipment_code, $items, $additional_data);
        $shipment->persist();

        App::getInstance()->event('order_shipment', ['object' => $this, 'items' => $items, 'shipment' => $shipment]);

        return $shipment;
    }

    /**
     * Get the shipments associated with this order
     *
     * @return BaseCollection|null
     */
    public function getShipments() : ?BaseCollection
    {
        if (!$this->getId()) {
            return null;
        }

        return OrderShipment::getCollection()->where(['order_id' => $this->getId()])->addOrder(['created_at' => 'DESC']);
    }
}
