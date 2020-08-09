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
use \App\Base\Traits\WithOwnerTrait;
use DateTime;

/**
 * Cront Task Model
 *
 * @method int getId()
 * @method string getTitle()
 * @method string getCronTaskCallable()
 * @method string getSchedule()
 * @method boolean getActive()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class CronTask extends BaseModel
{
    use WithOwnerTrait;

    /**
     * gets information url about schedule
     *
     * @return string
     */
    public function getInfoUrl()
    {
        return 'https://crontab.guru/#'. str_replace(" ", "_", $this->schedule);
    }
}
