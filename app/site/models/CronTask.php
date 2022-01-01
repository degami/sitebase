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
use App\Base\Traits\WithOwnerTrait;
use DateTime;

/**
 * Cron Task Model
 *
 * @method int getId()
 * @method string getTitle()
 * @method string getCronTaskCallable()
 * @method string getSchedule()
 * @method bool getActive()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setTitle(string $title)
 * @method self setCronTaskCallable(string $cron_task_callable)
 * @method self setSchedule(string $schedule)
 * @method self setActive(bool $active)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class CronTask extends BaseModel
{
    use WithOwnerTrait;

    /**
     * gets information url about schedule
     *
     * @return string
     */
    public function getInfoUrl(): string
    {
        return 'https://crontab.guru/#' . str_replace(" ", "_", $this->getSchedule());
    }
}
