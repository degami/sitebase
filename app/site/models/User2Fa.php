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
use DateTime;

/**
 * User 2Fa Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getUserId()
 * @method string getSecret()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUserId(int $user_id)
 * @method self setSecret(string $secret)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class User2Fa extends BaseModel
{
    /**
     * gets model table name
     *
     * @return string
     */
    public static function defaultTableName(): string
    {
        return 'users_2fa';
    }
}
