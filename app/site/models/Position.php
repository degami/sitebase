<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Models;

use App\Base\Abstracts\Models\ModelWithLocation;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;

/**
 * Position Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getLocale()
 * @method int getUserId()
 * @method float getLatitude()
 * @method float getLongitude()
 * @method bool getActive()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setLocale(string $locale)
 * @method self setUserId(int $user_id)
 * @method float setLatitude(float $latitude)
 * @method float setLongitude(float $longitude)
 * @method self setActive(bool $active)
 */
class Position extends ModelWithLocation
{
    use WithOwnerTrait;
    use WithWebsiteTrait;

}
