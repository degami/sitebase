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
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;

/**
 * Store Credit Transaction Model
 * 
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method int getStoreCreditId()
 * @method float getAmount()
 * @method string getMovementType()
 * @method string getTransactionId()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setStoreCreditId(int $store_credit_id)
 * @method self setAmount(float $amount)
 * @method self setMovementType(string $movement_type)
 * @method self setTransactionId(string $transaction_id)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
class StoreCreditTransaction extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    public const MOVEMENT_TYPE_INCREASE = 'increase';
    public const MOVEMENT_TYPE_DECREASE = 'decrease';

    protected ?StoreCredit $store_credit = null;
    
    /**
     * Set the store credit for this transaction
     *
     * @param StoreCredit $store_credit
     * @return self
     */
    public function setStoreCredit(StoreCredit $store_credit): self
    {
        $this->store_credit = $store_credit;
        $this->setStoreCreditId($store_credit->getId());
        return $this;
    }

    public function getStoreCredit(): ?StoreCredit
    {
        if ($this->store_credit !== null) {
            return $this->store_credit;
        }

        return $this->setStoreCredit(StoreCredit::load($this->getStoreCreditId()))->store_credit;
    }
}
