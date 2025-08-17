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
 * Order Comment Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getOrderId()
 * @method int getUserId()
 * @method string getComment()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setOrderId(int $order_id)
 * @method self setUserId(int $user_id)
 * @method self setComment(string $comment)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderComment extends BaseModel
{
    use WithWebsiteTrait, WithOwnerTrait;

    protected ?Order $order = null;

    /**
     * Get the order associated with this comment
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
     * Set the order for this comment
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
}
