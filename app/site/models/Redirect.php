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

namespace App\Site\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;
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
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrlFrom(string $url_from)
 * @method self setUrlTo(string $url_to)
 * @method self setRedirectCode(string $redirect_code)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Redirect extends BaseModel
{
    use WithOwnerTrait;
    use WithWebsiteTrait;
}
