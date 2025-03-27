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

namespace App\Base\Cron\Tasks;

use App\Base\Abstracts\ContainerAwareObject;

/**
 * Cron HeartBeat
 */
class HeartBeat extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '*/5 * * * *';

    /**
     * pulse method
     *
     * @return string
     */
    public function pulse(): string
    {
        return 'beat';
    }
}
