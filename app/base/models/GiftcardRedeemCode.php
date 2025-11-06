<?php

namespace App\Base\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;


/**
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method int getOrderItemId()
 * @method string getCode()
 * @method float getCredit()
 * @method boolean getRedeemed()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setOrderItemId(int $order_item_id)
 * @method self setCode(string $code)
 * @method self setCredit(float $credit)
 * @method self setRedeemed(boolean $redeemed)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
class GiftcardRedeemCode extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?OrderItem $order_item = null;

    public function getOrderItem(): ?OrderItem
    {
        if (!is_null($this->order_item)) {
            return $this->order_item;
        }

        if (is_null($this->getOrderItemId())) {
            return null;
        }

        return $this->setOrderItem(OrderItem::load(['id' => $this->getOrderItemId()]))->order_item;
    }

    public function setOrderItem(OrderItem $order_item): self
    {
        $this->order_item = $order_item;
        $this->setOrderItemId($order_item->getId());

        return $this;
    }

    public static function createFromOrderItem(OrderItem $order_item, ?string $code = null): self
    {
        $giftCard = $order_item->getProduct();
        if (!($giftCard instanceof GiftCard)) {
            throw new \InvalidArgumentException("Order item is not a gift card");
        }

        $giftcard_redeem_code = new self();
        $giftcard_redeem_code->setOrderItem($order_item);
        $giftcard_redeem_code->setCode($code ?? bin2hex(random_bytes(16)));
        $giftcard_redeem_code->setCredit($giftCard->getCredit());
        $giftcard_redeem_code->setRedeemed(false);
        $giftcard_redeem_code->setUserId($order_item->getUserId());
        $giftcard_redeem_code->setWebsiteId($order_item->getWebsiteId());

        return $giftcard_redeem_code;
    }
}
