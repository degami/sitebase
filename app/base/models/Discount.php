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
use DateTime;


/**
 * Discount Model
 *
 * @method int getId()
 * @method int getUserId()
 * @method int getWebsiteId()
 * @method string getTitle()
 * @method string getCode()
 * @method bool getActive()
 * @method float getDiscountAmount()
 * @method string getDiscountType()
 * @method int getMaxUsages()
 * @method int getMaxUsagesPerUser()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(int $user_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setTitle(string $title)
 * @method self setCode(string $code)
 * @method self setActive(bool $active)
 * @method self setDiscountAmount(float $discount_amount)
 * @method self setDiscountType(string $discount_type)
 * @method self setMaxUsages(int $max_usages)
 * @method self setMaxUsagesPerUser(int $max_usages_per_user)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Discount extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;
}
