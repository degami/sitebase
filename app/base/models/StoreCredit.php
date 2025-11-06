<?php

namespace App\Base\Models;

use App\Base\Abstracts\Models\AccountModel;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Models\Website;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;

/**
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method float getCredit()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setCredit(float $credit)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
class StoreCredit extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    /**
     * Returns a store credit transaction
     *
     * @param float $amount
     * @return self
     */
    public function makeTransaction(float $amount, AccountModel $user, Website $website): StoreCreditTransaction
    {
        $transaction = new StoreCreditTransaction();
        $transaction->setStoreCredit($this)
            ->setAmount($amount)
            ->setMovementType($amount >= 0 ? StoreCreditTransaction::MOVEMENT_TYPE_INCREASE : StoreCreditTransaction::MOVEMENT_TYPE_DECREASE)
            ->setTransactionId(uniqid('sc_txn_'))
            ->setUserId($user->getId())
            ->setWebsiteId($website->getId())
            ->persist();

        $this->setCredit($this->getCredit() + $amount)->persist();
        return $transaction;
    }

    /**
     * Returns the transactions related to this store credit
     *
     * @return BaseCollection
     */
    public function getTransactions(): BaseCollection
    {
        return StoreCreditTransaction::getCollection()
            ->where(['store_credit_id' => $this->getId()]);
    }
}
