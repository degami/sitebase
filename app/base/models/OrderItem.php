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
use App\Base\Interfaces\Model\ProductInterface;
use DateTime;

/**
 * Order Item Model
 *
 * @method int getId()
 * @method int getOrderId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method int getCartItemId()
 * @method string getProductClass()
 * @method int getProductId()
 * @method int getQuantity()
 * @method float getUnitPrice()
 * @method float getSubTotal()
 * @method float getDiscountAmount()
 * @method float getTaxAmount()
 * @method float getTotalInclTax()
 * @method string getCurrencyCode()
 * @method float getAdminUnitPrice()
 * @method float getAdminSubTotal()
 * @method float getAdminDiscountAmount()
 * @method float getAdminTaxAmount()
 * @method float getAdminTotalInclTax()
 * @method string getAdminCurrencyCode()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setOrderId(int $order_id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setCartItemId(int $cart_item_id)
 * @method self setProductClass(string $product_class)
 * @method self setProductId(int $product_id)
 * @method self setQuantity(int $quantity)
 * @method self setUnitPrice(float $unit_price)
 * @method self setSubTotal(float $sub_total)
 * @method self setDiscountAmount(float $discount_amount)
 * @method self setTaxAmount(float $tax_amount)
 * @method self setTotalInclTax(float $total_incl_tax)
 * @method self setCurrencyCode(string $currency_code)
 * @method self setAdminUnitPrice(float $admin_price)
 * @method self setAdminSubTotal(float $admin_sub_total)
 * @method self setAdminDiscountAmount(float $admin_discount_amount)
 * @method self setAdminTaxAmount(float $admin_tax_amount)
 * @method self setAdminTotalInclTax(float $admin_total_incl_tax)
 * @method self setAdminCurrencyCode(string $admin_currency_code)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderItem extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?Order $order = null;
    protected ?ProductInterface $product = null;

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * Get the order associated with this item
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        if (!$this->order) {
            $this->order = Order::load($this->order_id);
        }

        return $this->order;
    }

    /**
     * Set the order for this item
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
        $this->setAdminCurrencyCode($order->getAdminCurrencyCode());

        return $this;
    }

    /**
     * Get the product associated with this order item
     *
     * @return ProductInterface|null
     */
    public function getProduct() : ?ProductInterface
    {
        if ($this->product) {
            return $this->product;
        }

        if (!$this->getProductClass()) {
            return null;
        }

        if (!$this->getProductId()) {
            return App::getInstance()->containerMake($this->getProductClass());
        }

        /** @var ?ProductInterface $product */
        $product = App::getInstance()->containerCall([$this->getProductClass(), 'load'], ['id' => $this->getProductId()]);

        if (!$product instanceof ProductInterface) {
            return null;
        }

        $this->product = $product;

        return $this->product;
    }

    /**
     * Set the product for this order item and update related properties
     *
     * @param ProductInterface $product
     * @return self
     */
    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;
        $this->setProductClass(get_class($product));
        $this->setProductId($product->getId());

        return $this;
    }

    /**
     * Check if this order item requires shipping
     *
     * @return bool
     */
    public function requireShipping(): bool
    {
        if (!$this->getProduct()) {
            return false;
        }

        if (!$this->getProduct()->isPhysical()) {
            return false;
        }

        if (is_null($this->getId())) {
            return true;
        }

        $stmt = App::getInstance()->getPdo()->prepare("
            SELECT SUM(quantity)
            FROM order_shipment_item
            WHERE order_item_id = :orderItemId
        ");

        $stmt->execute(['orderItemId' => $this->getId()]);

        $sumShipmentsQty = $stmt->fetchColumn();

        return $sumShipmentsQty < $this->getQuantity();
    }
}
