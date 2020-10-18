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
use DateTime;

/**
 * Cron Log Model
 *
 * @method int getId()
 * @method string getRunTime()
 * @method int getDuration()
 * @method string getTasks()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class CronLog extends BaseModel
{
}
