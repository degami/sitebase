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

namespace App\Base\Interfaces\Queue;

interface QueueMessageInterface
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const ENDED_OK = 1;
    public const ENDED_KO = 0;
}