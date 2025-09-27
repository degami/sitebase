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
 * Cart Model
 *
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method bool getIsActive()
 * @method float getSubTotal()
 * @method float getDiscountAmount()
 * @method float getTaxAmount()
 * @method float getShippingAmount()
 * @method float getTotalInclTax()
 * @method string getCurrencyCode()
 * @method float getAdminSubTotal()
 * @method float getAdminDiscountAmount()
 * @method float getAdminTaxAmount()
 * @method float getAdminShippingAmount()
 * @method float getAdminTotalInclTax()
 * @method string getAdminCurrencyCode()
 * @method int getShippingAddressId()
 * @method int getBillingAddressId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setIsActive(bool $is_active)
 * @method self setSubTotal(float $sub_total)
 * @method self setDiscountAmount(float $discount_amount)
 * @method self setTaxAmount(float $tax_amount)
 * @method self setShippingAmount(float $shipping_amount)
 * @method self setTotalInclTax(float $total_incl_tax)
 * @method self setCurrencyCode(string $currency_code)
 * @method self setAdminSubTotal(float $admin_sub_total)
 * @method self setAdminDiscountAmount(float $admin_discount_amount)
 * @method self setAdminTaxAmount(float $admin_tax_amount)
 * @method self setAdminShippingAmount(float $admin_shipping_amount)
 * @method self setAdminTotalInclTax(float $admin_total_incl_tax)
 * @method self setAdminCurrencyCode(string $admin_currency_code)
 * @method self setShippingAddressId(int $shipping_address_id)
 * @method self setBillingAddressId(int $billing_address_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Cart extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected $items = [];
    protected ?array $discounts = null;
    protected ?Address $billingAddress = null;
    protected ?Address $shippingAddress = null;

    /**
     * Get the name of the model
     * 
     * @return \App\Base\Models\CartItem[]
     */
    public function getItems(): array
    {
        $this->loadCartItems();

        return $this->items;
    }

    /**
     * Sets items
     * 
     * @param array $items
     * 
     * @return static
     */
    public function setItems(array $items) : static
    {
        $cartItems = [];
        foreach ($items as $item) {
            if (!($item instanceof CartItem)) {
                continue;
            }

            $cartItems[] = $item->setCart($this);
        }

        $this->items = $cartItems;

        return $this;
    }

    /**
     * Load cart items from the database if not already loaded
     *
     * @return static
     */
    protected function loadCartItems(): static
    {
        if (empty($this->items)) {
            foreach (CartItem::getCollection()->where(['cart_id' => $this->getId()]) as $item) {
                if ($item instanceof CartItem) {
                    $this->items[] = $item;
                }
            }
        }

        return $this;
    }

    /**
     * Load discounts associated with the cart
     *
     * @return static
     */
    protected function loadDiscounts(): static
    {
        if (empty($this->discounts)) {
            foreach (CartDiscount::getCollection()->where(['cart_item_id' => null, 'cart_id' => $this->getId()])->getItems() as $discount) {
                if (!is_array($this->discounts)) {
                    $this->discounts = [];
                }

                $this->discounts[] = $discount;
            }
        }

        return $this;        
    }

    /**
     * Full load of the cart, including items and discounts
     *
     * @return static
     */
    public function fullLoad(): static
    {
        $this->resetItems()->resetDiscounts()->loadCartItems()->loadDiscounts();
        $this->getWebsite();

        foreach ($this->getCartItems() ?? [] as $cartItem) {
            /** @var CartItem $cartItem */
            $cartItem->fullLoad();
        }

        return $this;
    }

    /**
     * Reset discounts
     *
     * @return static
     */
    public function resetDiscounts() : static
    {
        $this->discounts = null;
        return $this;
    }

    /**
     * Reset items
     *
     * @return static
     */
    public function resetItems() : static
    {
        $this->items = [];
        return $this;
    }

    /**
     * Reset billing and shipping addresses
     *
     * @return static
     */
    public function resetBillingAddress() : static
    {
        $this->billingAddress = null;
        return $this;
    }

    /**
     * Reset shipping address
     *
     * @return static
     */
    public function resetShippingAddress() : static
    {
        $this->shippingAddress = null;
        return $this;
    }

    /**
     * Get the billing address
     *
     * @return Address|null
     */
    public function getBillingAddress() : ?Address
    {
        if ($this->billingAddress) {
            return $this->billingAddress;
        }

        if (!$this->getBillingAddressId()) {
            return null;
        }

        return $this->setBillingAddress(Address::load($this->getBillingAddressId()))->billingAddress;
    }

    /**
     * Set the billing address
     *
     * @param Address $billingAddress
     * @return static
     */
    public function setBillingAddress(Address $billingAddress) : static
    {
        $this->billingAddress = $billingAddress;
        $this->setBillingAddressId($billingAddress->getId());

        return $this;
    }

    /**
     * Get the shipping address
     *
     * @return Address|null
     */
    public function getShippingAddress() : ?Address
    {
        if ($this->shippingAddress) {
            return $this->shippingAddress;
        }

        if (!$this->getShippingAddressId()) {
            return null;
        }

        return $this->setShippingAddress(Address::load($this->getShippingAddressId()))->shippingAddress;
    }

    /**
     * Set the shipping address
     *
     * @param Address $shippingAddress
     * @return static
     */
    public function setShippingAddress(Address $shippingAddress) : static
    {
        $this->shippingAddress = $shippingAddress;
        $this->setShippingAddressId($shippingAddress->getId());

        return $this;
    }

    /**
     * Add a product to the cart
     *
     * @param ProductInterface $product
     * @param int $quantity
     * @return CartItem
     */
    public function addProduct(ProductInterface $product, int $quantity = 1): CartItem
    {
        $cartItem = new CartItem();
        $cartItem->setCurrencyCode($this->getCurrencyCode())
            ->setAdminCurrencyCode($this->getAdminCurrencyCode());

        $cartItem->setProduct($product)
            ->setCartId($this->getId())
            ->setQuantity($quantity)
            ->setUserId($this->getUserId())
            ->setWebsiteId($this->getWebsiteId());

        $this->items[] = $cartItem;

        return $cartItem;
    }

    /**
     * Remove a product from the cart
     *
     * @param ProductInterface $product
     * @return static
     */
    public function removeProduct(ProductInterface $product): static
    {
        $cartItem = null;

        if (!empty($this->items)) {
            $cartItem = current(array_filter($this->items, function ($item) use ($product) {
                return $item instanceof CartItem && $item->getProductId() === $product->getId() &&
                    $item->getProductClass() === get_class($product);
            }));
        } else if ($this->getId()) {
            $cartItem = CartItem::getCollection()->where([
                'cart_id' => $this->getId(),
                'product_class' => get_class($product),
                'product_id' => $product->getId(),
            ])->getFirst();
        }

        if ($cartItem) {
            return $this->removeItem($cartItem);
        }

        return $this;
    }

    /**
     * Get a cart item by its ID
     *
     * @param int $cartItemId
     * @return CartItem|null
     */
    public function getCartItem(int $cartItemId): ?CartItem
    {
        foreach ($this->getItems() as $item) {
            if ($item instanceof CartItem && $item->getId() === $cartItemId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Remove an item from the cart
     *
     * @param CartItem|int $cartItem
     * @return static
     */
    public function removeItem(CartItem|int $cartItem): static
    {
        if ($cartItem instanceof CartItem) {
            $cartItemId = $cartItem->getId();
        } else {
            $cartItemId = (int)$cartItem;
        }

        $this->items = array_filter($this->getItems(), function ($item) use ($cartItemId) {
            return $item->getId() !== $cartItemId;
        });

        if (is_numeric($cartItem) && $cartItem > 0) {
            $cartItem = CartItem::getCollection()->where(['id' => $cartItemId])->getFirst();
        }

        if ($cartItem instanceof CartItem && $cartItem->getId()) {
            $cartItem->delete();
        }

        return $this->save();
    }

    /**
     * Get discounts associated with the cart
     *
     * @return \App\Base\Models\CartDiscount[]|null
     */
    public function getDiscounts() : ?array
    {
        if (!$this->getId()) {
            return null;
        }

        return $this->loadDiscounts()->discounts;
    }

    /**
     * Sets cart discounts
     * 
     * @param array $discounts
     * 
     * @return static
     */
    public function setDiscounts(array $discounts) : static
    {
        $cartDiscounts = [];
        foreach ($discounts as $discount) {
            if (!($discount instanceof CartDiscount)) {
                continue;
            }

            $cartDiscounts[] = $discount->setCart($this);
        }

        $this->discounts = $cartDiscounts;

        return $this;
    }
    
    public function prePersist() : BaseModel
    {
        $this->calculate();
        return parent::prePersist();
    }

    public function postPersist(): BaseModel
    {
        // propagate cart_id to cart items
        foreach ($this->getItems() as $item) {
            /** @var CartItem $item */
            $item->setCart($this)->persist();
        }

        // propagate cart_id to cart discounts
        foreach ($this->getDiscounts() ?? [] as $discount) {
            /** @var CartDiscount $discount */
            $discount->setCart($this)->persist();
        }

        return parent::postPersist();
    }

    /**
     * Calculate the cart totals
     *
     * @return static
     */
    public function calculate() : self
    {
        $sub_total = 0; $discount_amount = 0; $tax_amount = 0;
        $admin_sub_total = 0; $admin_discount_amount = 0; $admin_tax_amount = 0;

        foreach ($this->getDiscounts() ?? [] as $discount) {
            /** @var CartDiscount $discount */
            $discount_amount += -1* abs($discount->getDiscountAmount());
            $admin_discount_amount += $discount->getAdminDiscountAmount() ?: App::getInstance()->getUtils()->convertFromCurrencyToCurrency($discount->getDiscountAmount(), $discount->getCurrencyCode(), $discount->getAdminCurrencyCode());
        }

        foreach ($this->getItems() as $cartItem) {
            /** @var CartItem $cartItem */
            $cartItem->calculate();

            $sub_total += $cartItem->getSubTotal();
            $discount_amount += -1 * abs($cartItem->getDiscountAmount());
            $tax_amount += $cartItem->getTaxAmount();

            $admin_sub_total += $cartItem->getAdminSubTotal();
            $admin_discount_amount += $cartItem->getAdminDiscountAmount();
            $admin_tax_amount += $cartItem->getAdminTaxAmount();
        }

        $this->calculateShipping();

        $total_incl_tax = $sub_total + $discount_amount + $tax_amount + floatval($this->getShippingAmount());
        $admin_total_incl_tax = $admin_sub_total + $admin_discount_amount + $admin_tax_amount + floatval($this->getAdminShippingAmount());

        return $this
            ->setSubTotal($sub_total)
            ->setDiscountAmount($discount_amount)
            ->setTaxAmount($tax_amount)
            ->setTotalInclTax($total_incl_tax)
            ->setAdminSubTotal($admin_sub_total)
            ->setAdminDiscountAmount($admin_discount_amount)
            ->setAdminTaxAmount($admin_tax_amount)
            ->setAdminTotalInclTax($admin_total_incl_tax);
    }

    /**
     * Check if the cart requires shipping
     *
     * @return bool
     */
    public function requireShipping(): bool
    {
        foreach ($this->getItems() as $item) {
            /** @var CartItem $item */
            if ($item->requireShipping()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate shipping costs for the cart
     *
     * @return float
     */
    public function calculateShipping(): float
    {
        if (!$this->requireShipping()) {
            $this->setShippingAmount(0.0);
            $this->setAdminShippingAmount(0.0);
            return 0.0;
        }

        if (!$this->getShippingAddress()) {
            // If no shipping address is set, we cannot calculate shipping
            $this->setShippingAmount(0.0);
            $this->setAdminShippingAmount(0.0);
            return 0.0;
        }

        $shipping_amount = 0.0;

        $this->setShippingAmount($shipping_amount);
        $this->setAdminShippingAmount(App::getInstance()->getUtils()->convertFromCurrencyToCurrency($shipping_amount, $this->getCurrencyCode(), $this->getAdminCurrencyCode()));
        return $shipping_amount;
    }

    /**
     * Duplicate Model
     * 
     * @return BaseModel
     */
    public function duplicate() : BaseModel
    {
        /** @var Cart $copy */
        $copy = parent::duplicate();


        if ($this->getItems()) {
            $itemsCopy = [];
            foreach ($this->getItems() as $item) {
                $itemsCopy[] = $item->duplicate();
            }
            $copy->setItems($itemsCopy);
        }

        if ($this->getDiscounts()) {
            $discountsCopy = [];
            foreach ($this->getDiscounts() as $discount) {
                $discountsCopy[] = $discount->duplicate();
            }

            $copy->setDiscounts($discountsCopy);
        }

        return $copy;
    }
}
