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
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Order Shipment Item Model
 *
 * @method int getId()
 * @method int getShipmentId()
 * @method int getOrderItemId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method int getQuantity()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setShipmentId(int $shipment_id)
 * @method self setOrderItemId(int $order_item_id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setQuantity(int $quantity)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderShipmentItem extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?OrderShipment $shipment = null;
    protected ?OrderItem $order_item = null;

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * Get the shipment associated with this item
     *
     * @return OrderShipment
     */
    public function getShipment(): OrderShipment
    {
        if (!$this->shipment) {
            $this->shipment = OrderShipment::load($this->shipment_id);
        }

        return $this->shipment;
    }

    /**
     * Set the shipment for this item
     *
     * @param OrderShipment $order
     * @return self
     */
    public function setShipment(OrderShipment $shipment): self
    {
        $this->shipment = $shipment;
        $this->setShipmentId($shipment->getId());
        $this->setUserId($shipment->getUserId());
        $this->setWebsiteId($shipment->getWebsiteId());

        return $this;
    }

    /**
     * Get the order item associated with this item
     *
     * @return OrderItem|null
     */
    public function getOrderItem() : ?OrderItem
    {
        if ($this->order_item) {
            return $this->order_item;
        }

        if (!$this->getOrderItemId()) {
            return null;
        }

        /** @var ?OrderItem $orderItem */
        $order_item = OrderItem::load($this->getOrderItemId());

        if (!$order_item instanceof OrderItem) {
            return null;
        }

        return ($this->setOrderItem($order_item))->order_item;
    }

    /**
     * Set the order item for this item and update related properties
     *
     * @param OrderItem $orderItem
     * @return self
     */
    public function setOrderItem(OrderItem $orderItem): self
    {
        $this->order_item = $orderItem;
        $this->setOrderItemId($orderItem->getId());

        return $this;
    }
}
