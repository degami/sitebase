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
 * Cart Discount Model
 *
 * @method int getId()
 * @method int getCartId()
 * @method int getCartItemId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method int getInitialDiscountId()
 * @method float getDiscountAmount()
 * @method string getCurrencyCode()
 * @method float getAdminDiscountAmount()
 * @method string getAdminCurrencyCode()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setCartId(int $cart_id)
 * @method self setCartItemId(int $cart_item_id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id
 * @method self setInitialDiscountId(int $initial_discount_id)
 * @method self setDiscountAmount(float $discount_amount)
 * @method self setCurrencyCode(string $currency_code)
 * @method self setAdminDiscountAmount(float $admin_discount_amount)
 * @method self setAdminCurrencyCode(string $admin_currency_code)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class CartDiscount extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?Cart $cart = null;
    protected ?CartItem $cartItem = null;
    protected ?Discount $initialDiscount = null;

    public function getCart(): Cart
    {
        if (!$this->cart) {
            $this->cart = Cart::load($this->cart_id);
        }

        return $this->cart;
    }

    public function getCartItem(): ?CartItem
    {
        if (!$this->cart_item_id) {
            return null;
        }

        if (!$this->cartItem) {
            $this->cartItem = CartItem::load($this->cart_item_id);
        }

        return $this->cartItem;
    }

    public function setCart(Cart $cart): self
    {
        $this->cart = $cart;
        $this->setCartId($cart->getId());
        $this->setUserId($cart->getUserId());
        $this->setWebsiteId($cart->getWebsiteId());
        $this->setCurrencyCode($cart->getCurrencyCode());
        $this->setAdminCurrencyCode($cart->getAdminCurrencyCode());

        return $this;
    }

    public function setCartItem(CartItem $cartItem): self
    {
        $this->cartItem = $cartItem;
        $this->setCartItemId($cartItem->getId());
        $this->setCart($cartItem->getCart());

        return $this;
    }

    public function getInitialDiscount(): ?Discount
    {
        if ($this->initialDiscount) {
            return $this->initialDiscount;
        }

        if (!$this->getInitialDiscountId()) {
            return null;
        }

        return $this->setInitialDiscount(Discount::load($this->getInitialDiscountId()))->initialDiscount;
    }

    public function setInitialDiscount(Discount $discount): self
    {
        $this->initialDiscount = $discount;
        $this->setInitialDiscountId($discount->getId());

        return $this;
    }

    public static function createFromDiscount(Discount $discount, Cart|CartItem $target): self
    {
        $cartDiscount = new self();
        $cartDiscount
            ->setWebsiteId($target->getWebsiteId())
            ->setInitialDiscountId($discount->getId())
            ->setCurrencyCode($target->getCurrencyCode())
            ->setAdminCurrencyCode($target->getAdminCurrencyCode());

        if ($target instanceof Cart) {
            $cartDiscount->setCartId($target->getId());
        }
        if ($target instanceof CartItem) {
            $cartDiscount->setCartItemId($target->getId());
        }

        $cartDiscount->setUserId($target->getUserId());

        match ($discount->getDiscountType()) {
            'fixed' => $cartDiscount->setDiscountAmount($discount->getDiscountAmount()),
            'percentage' => $cartDiscount->setDiscountAmount(
                $target instanceof CartItem
                    ? $target->getUnitPrice() * ($discount->getDiscountAmount() / 100) * $target->getQuantity()
                    : $target->getSubTotal() * ($discount->getDiscountAmount() / 100)
            ),
            default => throw new \InvalidArgumentException('Unknown discount type: ' . $discount->getDiscountType()),
        };

        $cartDiscount
            ->setAdminDiscountAmount(App::getInstance()->getUtils()->convertFromCurrencyToCurrency(
                $cartDiscount->getDiscountAmount(),
                $cartDiscount->getCurrencyCode(),
                $cartDiscount->getAdminCurrencyCode()
            ));

        return $cartDiscount;
    }
}
