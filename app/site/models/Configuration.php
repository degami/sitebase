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

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Configuration Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getPath()
 * @method string getValue()
 * @method string getLocale()
 * @method bool getIsSystem()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setPath(string $path)
 * @method self setValue(string $value)
 * @method self setLocale(string $locale)
 * @method self setIsSystem(bool $is_system)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Configuration extends BaseModel
{
    use WithWebsiteTrait;
}
