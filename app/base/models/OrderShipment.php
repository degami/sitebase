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
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;
use DateTime;

/**
 * Order Shipment Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getOrderId()
 * @method int getUserId()
 * @method string getShippingMethod()
 * @method string getShipmentCode()
 * @method string getStatus()
 * @method string getAdditionalData()
 * @method float getLatitude()
 * @method float getLongitude()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setOrderId(int $order_id)
 * @method self setUserId(int $user_id)
 * @method self setShippingMethod(string $shipping_method)
 * @method self setShipmentCode(string $shipment_code)
 * @method self setStatus(string $status)
 * @method self setAdditionalData(string $additional_data)
 * @method self setLatitude(float $latitude)
 * @method self setLongitude(float $longitude)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderShipment extends BaseModel
{
    use WithWebsiteTrait, WithOwnerTrait;

    protected ?Order $order = null;
    protected array $items = [];

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

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

        return $this;
    }

    /**
     * Get the shipment items
     *
     * @return \App\Base\Models\OrderShipmentItem[]
     */
    public function getItems(): array
    {
        if ($this->items) {
            return $this->items;
        }

        if (!$this->getId()) {
            return [];
        }

        return $this->setItems(OrderShipmentItem::getCollection()->where(['shipment_id' => $this->getId()])->getItems())->items;
    }

    /**
     * Set the shipment items
     *
     * @param \App\Base\Models\OrderShipmentItem[] $items
     * @return self
     */
    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * gets current latitude
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return (float) $this->getData('latitude');
    }

    /**
     * gets current longitude
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return (float) $this->getData('longitude');
    }

    /**
     * gets current location
     */
    public function getCurrentLocation(): array
    {
        return ['latitude' => $this->getLatitude(), 'longitude' => $this->getLongitude()];
    }

    /**
     * Create a new OrderShipment instance for a given order
     *
     * @param Order $order
     * @param string $shipping_method
     * @param string $shipment_code
     * @param array $items
     * @param mixed $additional_data
     * @return self
     */
    public static function createForOrder(Order $order, string $shipping_method, string $shipment_code, array $items, $additional_data = null) : self
    {
        $shipment = new self();
        $shipment->setOrder($order);
        $shipment->setShippingMethod($shipping_method);
        $shipment->setShipmentCode($shipment_code);
        if (!isJson($additional_data)) {
            $additional_data = json_encode($additional_data);
        }
        $shipment->setAdditionalData($additional_data);

        $shipmentItems = [];
        foreach ($items as $item) {
            $orderItem = $quantity = null;

            if (is_array($item)) {
                /** @var OrderItem $orderItem */
                $orderItem = $item['order_item'];
                $quantity = $item['quantity'] ?? $orderItem->getQuantity();
            } else if ($item instanceof OrderItem) {
                $orderItem = $item;
                $quantity = $item->getQuantity();
            }

            if (is_null($orderItem)) {
                continue;
            }

            /** @var OrderShipmentItem $shipmentItem */
            $shipmentItem = App::getInstance()->containerMake(OrderShipmentItem::class);
            $shipmentItem
                ->setOrderItem($orderItem)
                ->setQuantity($quantity)
                ->setShipment($shipment);

            $shipmentItems[] = $shipmentItem;
        }
        $shipment->setItems($shipmentItems);

        return $shipment;
    }

    public function postPersist(array $persistOptions = []): BaseModel
    {
        // propagate order_id to order items
        foreach ($this->getItems() as $item) {
            /** @var OrderShipmentItem $item */
            $item->setShipment($this)->persist();
        }

        return parent::postPersist($persistOptions);
    }
}
