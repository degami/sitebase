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
use \App\Base\Traits\WithOwnerTrait;
use DateTime;

/**
 * Redirect Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrlFrom()
 * @method string getUrlTo()
 * @method string getRedirectCode()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class Redirect extends BaseModel
{
    use WithWebsiteTrait, WithOwnerTrait;
}
