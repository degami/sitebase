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
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Cart Item Model
 *
 * @method int getId()
 * @method int getCartId()
 * @method int getUserId()
 * @method int getWebsiteId()
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
 * @method self setCartId(int $cart_id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id
 * @method self setProductClass(string $product_class)
 * @method self setProductId(int $product_id)
 * @method self setQuantity(int $quantity)
 * @method self setUnitPrice(float $price)
 * @method self setSubTotal(float $sub_total)
 * @method self setDiscountAmount(float $discount_amount)
 * @method self setTaxAmount(float $tax_amount)
 * @method self setTotalInclTax(float $total_incl_tax)
 * @method self setCurrencyCode(string $currency_code)
 * @method self setAdminUnitPrice(float $admin_price)
 * @method self setAdminSubTotal(float $admin_total)
 * @method self setAdminDiscountAmount(float $admin_discount_amount)
 * @method self setAdminTaxAmount(float $admin_tax_amount)
 * @method self setAdminTotalInclTax(float $admin_total_incl_tax)
 * @method self setAdminCurrencyCode(string $admin_currency_code)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class CartItem extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?Cart $cart = null;
    protected ?ProductInterface $product = null;
    protected ?array $discounts = null;

    public function getCart(): Cart
    {
        if (!$this->cart) {
            $this->cart = Cart::load($this->cart_id);
        }

        return $this->cart;
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

    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;
        $this->setProductClass(get_class($product));
        $this->setProductId($product->getId());
        $this->setUnitPrice($product->getPrice());
        $this->setAdminUnitPrice(App::getInstance()->getUtils()->convertFromCurrencyToCurrency($this->getUnitPrice(), $this->getCurrencyCode(), $this->getAdminCurrencyCode()));

        return $this;
    }

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

    public function calculate() : self
    {
        $sub_total = $this->calculateSubTotal();
        $discount_amount = -1 * abs($this->calculateDiscount());
        $tax_amount = $this->calculateTax($this->getCart()->getBillingAddress()?->getCountryCode());

        $this->setTotalInclTax($sub_total + $discount_amount + $tax_amount);
        return $this;
    }

    protected function loadDiscounts(): static
    {
        if (empty($this->discounts)) {
            foreach (CartDiscount::getCollection()->where(['cart_item_id' => $this->getId(), 'cart_id' => $this->getCartId()]) as $discount) {
                if (!is_array($this->discounts)) {
                    $this->discounts = [];
                }

                $this->discounts[] = $discount;
            }
        }

        return $this;        
    }

    public function fullLoad(): static
    {
        return $this->loadDiscounts();
    }

    protected function getDiscounts() : ?array
    {
        if (!$this->getId()) {
            return null;
        }

        $this->loadDiscounts();

        return $this->discounts;
    }

    public function calculateSubTotal() : float
    {
        $sub_total = $this->getUnitPrice() * $this->getQuantity();
        $this->setSubTotal($sub_total);
        $this->setAdminSubTotal(App::getInstance()->getUtils()->convertFromCurrencyToCurrency($sub_total, $this->getCurrencyCode(), $this->getAdminCurrencyCode()));
        return $sub_total;        
    }

    public function calculateDiscount() : float
    {
        $discount_amount = 0.0;
        foreach ($this->getDiscounts() ?? [] as $discount) {
            /** @var CartDiscount $discount */
            $discount_amount += $discount->getDiscountAmount();
        }

        $this->setDiscountAmount(-1 * abs($discount_amount));
        $this->setAdminDiscountAmount(App::getInstance()->getUtils()->convertFromCurrencyToCurrency($discount_amount, $this->getCurrencyCode(), $this->getAdminCurrencyCode()));
        return $discount_amount;
    }

    public function calculateTax(?string $countryCode = null) : float
    {
        $tax_amount = 0.0;

        /** @var TaxRate $taxRate */
        $taxRate = TaxRate::getCollection()
            ->where([
                'website_id' => $this->getWebsiteId(), 
                'tax_class_id' => $this->getProduct()->getTaxClassId(), 
                'country_code' => $countryCode
            ])
            ->getFirst();

        if (!$taxRate) {
            $taxRate = TaxRate::getCollection()
                ->where([
                    'website_id' => $this->getWebsiteId(), 
                    'tax_class_id' => $this->getProduct()->getTaxClassId(), 
                    'country_code' => '*'
                ])
                ->getFirst();
        }

        if ($taxRate) {
            $tax_amount = $this->getProduct()->getPrice() * ($taxRate->getRate() / 100) * $this->getQuantity();
        }

        $this->setTaxAmount($tax_amount);
        $this->setAdminTaxAmount(App::getInstance()->getUtils()->convertFromCurrencyToCurrency($tax_amount, $this->getCurrencyCode(), $this->getAdminCurrencyCode()));
        return $tax_amount;
    }
    
    public function prePersist() : BaseModel
    {
        $this->calculate();
        return parent::prePersist();
    }

    public function postPersist(): BaseModel
    {
        // propagate cart_item_id to cart discounts
        foreach ($this->getDiscounts() ?? [] as $discount) {
            /** @var CartDiscount $discount */
            $discount->setCartItem($this)->persist();
        }

        return parent::postPersist();
    }

    public function requireShipping(): bool
    {
        if (!$this->getProduct()) {
            return false;
        }

        return $this->getProduct()->isPhysical();
    }
}
