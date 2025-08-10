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
use DateTime;

/**
 * Address Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getTaxClassId()
 * @method string getCountryCode()
 * @method float getRate()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setTaxClassId(int $tax_class_id)
 * @method self setCountryCode(string $country_code)
 * @method self setRate(float $rate)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class TaxRate extends BaseModel
{
    use WithWebsiteTrait;

    protected ?TaxClass $taxClass = null;

    public function getTaxClass(): ?TaxClass
    {
        if ($this->taxClass) {
            return $this->taxClass;
        }

        if (!$this->getTaxClassId()) {
            return null;
        }

        return $this->setTaxClass(TaxClass::load($this->getTaxClassId()))->taxClass;
    }

    public function setTaxClass(TaxClass $taxClass): self
    {
        $this->taxClass = $taxClass;
        $this->setTaxClassId($taxClass->getId());
        return $this;
    }
}
