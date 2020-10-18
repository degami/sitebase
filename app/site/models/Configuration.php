<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Models;

use \App\Base\Abstracts\Models\BaseModel;
use \App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Configuration Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getPath()
 * @method string getValue()
 * @method string getLocale()
 * @method boolean getIsSystem()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class Configuration extends BaseModel
{
    use WithWebsiteTrait;
}
