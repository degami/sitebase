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

interface QueueWorkerInterface
{
    /**
     * do message work phase
     *
     * @param array $message_data
     * @return mixed
     */
    public function processMessage(array $message_data): mixed;
}