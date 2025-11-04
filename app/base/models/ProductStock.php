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
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Product Stock Model
 *
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method string getProductClass()
 * @method int getProductId()
 * @method int getQuantity()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setProductClass(string $product_class)
 * @method self setProductId(int $product_id)
 * @method self setQuantity(int $quantity)
 * @method self setAdminCurrencyCode(string $admin_currency_code)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class ProductStock extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?ProductInterface $product = null;

    /**
     * Set the product for this stock item and update related properties
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
     * Get the product associated with this stock item
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
     * Get the stock movements associated with this stock item
     *
     * @return BaseCollection
     */
    public function getMovements() : BaseCollection
    {
        return StockMovement::getCollection()
            ->where(['stock_id' => $this->getId()]);
    }

    public function consolidateStock(): self
    {
        // @todo - check resulting query
        $collection = $this->getMovements()->orWhere([
            ['movement_type' => StockMovement::MOVEMENT_TYPE_INCREASE],
            ['movement_type' => StockMovement::MOVEMENT_TYPE_DECREASE, 'order_item_id:not' => null]
        ]);

        $this->setQuantity($this->getQuantity() + array_sum($collection->map(function (StockMovement $movement) {
            return match($movement->getMovementType()) {
                StockMovement::MOVEMENT_TYPE_INCREASE => 1 * $movement->getQuantity(),
                StockMovement::MOVEMENT_TYPE_DECREASE => -1 * $movement->getQuantity(),
                default => 0,
            };
        })));

        $this->persist();

        $collection->remove();

        return $this;
    }
}
