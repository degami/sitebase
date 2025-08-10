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
use DateTime;

/**
 * Order Status Change Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getOrderId()
 * @method string getStatusFrom()
 * @method string getStatusTo()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setOrderId(int $order_id)
 * @method self setStatusFrom(string $status_from)
 * @method self setStatusTo(string $status_to)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderStatusChange extends BaseModel
{
    use WithWebsiteTrait;

    protected ?Order $order = null;

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

    public function setOrder(Order $order): self
    {
        $this->order = $order;
        $this->setOrderId($order->getId());
        $this->setWebsiteId($order->getWebsiteId());

        return $this;
    }
}
