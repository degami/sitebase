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
use App\Base\Commands\Generate\Product;
use App\Base\Interfaces\Model\PhysicalProductInterface;
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Stock Movement Model
 *
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method int getStockId()
 * @method string getMovementType()
 * @method int getQuantity()
 * @method int getCartItemId()
 * @method int getOrderItemId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setStockId(int $stock_id)
 * @method self setMovementType(string $movement_type)
 * @method self setQuantity(int $quantity)
 * @method self setCartItemId(int $cart_item_id)
 * @method self setOrderItemId(int $order_item_id)
 * @method self setAdminCurrencyCode(string $admin_currency_code)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class StockMovement extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    public const MOVEMENT_TYPE_INCREASE = 'increase';
    public const MOVEMENT_TYPE_DECREASE = 'decrease';
    public const MAX_CART_THRESHOLD = 30; // minutes

    protected ?ProductStock $product_stock = null;
    protected ?CartItem $cart_item = null;
    protected ?OrderItem $order_item = null;

    /**
     * Set the product stock for this movement item
     *
     * @param ProductStock $product_stock
     * @return self
     */
    public function setProductStock(ProductStock $product_stock): self
    {
        $this->product_stock = $product_stock;
        $this->setStockId($product_stock->getId());

        return $this;
    }

    /**
     * Get the product stock associated with this movement item
     *
     * @return ProductStock|null
     */
    public function getProductStock(): ?ProductStock
    {
        if ($this->product_stock === null) {
            $this->product_stock = ProductStock::load($this->getStockId());
        }

        return $this->product_stock;
    }

    /**
     * Set the cart item for this movement item
     *
     * @param CartItem $cart_item
     * @return self
     */
    public function setCartItem(CartItem $cart_item): self
    {
        $this->cart_item = $cart_item;
        $this->setCartItemId($cart_item->getId());

        return $this;
    }

    /**
     * Get the cart item associated with this movement item
     *
     * @return CartItem|null
     */
    public function getCartItem(): ?CartItem
    {
        if ($this->cart_item === null) {
            $this->cart_item = CartItem::load($this->getCartItemId());
        }
        return $this->cart_item;
    }

    /**
     * Set the order item for this movement item
     *
     * @param OrderItem $order_item
     * @return self
     */
    public function setOrderItem(OrderItem $order_item): self
    {
        $this->order_item = $order_item;
        $this->setOrderItemId($order_item->getId());
        return $this;
    }

    /**
     * Get the order item associated with this movement item
     *
     * @return OrderItem|null
     */
    public function getOrderItem(): ?OrderItem
    {
        if ($this->order_item === null) {
            $this->order_item = OrderItem::load($this->getOrderItemId());
        }
        return $this->order_item;
    }

    public static function createForCartItem(CartItem $cart_item) : self
    {
        $movement = new self();
        $movement->setCartItem($cart_item);

        /** @var PhysicalProductInterface $product */
        $product = $cart_item->getProduct();

        /** @var ProductStock $stockItem */
        $stockItem = call_user_func([$product, 'getProductStock']);

        if (!$stockItem) {
            $stockItem = App::getInstance()->containerMake(ProductStock::class);
            $stockItem
                ->setProduct($product)
                ->setWebsiteId($cart_item->getWebsiteId())
                ->setUserId(0) // system user
                ->persist();
        }

        $movement->setProductStock($stockItem);

        $movement->setMovementType(self::MOVEMENT_TYPE_DECREASE);
        $movement->setQuantity($cart_item->getQuantity());
        $movement->setUserId($cart_item->getUserId());
        $movement->setWebsiteId($cart_item->getWebsiteId());

        return $movement;
    }

    public static function createForOrderItem(OrderItem $order_item) : self
    {
        $movement = new self();
        $movement->setOrderItem($order_item);

        /** @var PhysicalProductInterface $product */
        $product = $order_item->getProduct();

        /** @var ProductStock $stockItem */
        $stockItem = call_user_func([$product, 'getProductStock']);

        if (!$stockItem) {
            $stockItem = App::getInstance()->containerMake(ProductStock::class);
            $stockItem
                ->setProduct($product)
                ->setWebsiteId($order_item->getWebsiteId())
                ->setUserId(0) // system user
                ->persist();
        }

        $movement->setProductStock($stockItem);

        $movement->setMovementType(self::MOVEMENT_TYPE_DECREASE);
        $movement->setQuantity($order_item->getQuantity());
        $movement->setUserId($order_item->getUserId());
        $movement->setWebsiteId($order_item->getWebsiteId());

        return $movement;
    }
}
