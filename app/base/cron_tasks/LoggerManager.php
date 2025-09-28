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
use App\App;

/**
 * Logs manager cron
 */
class LoggerManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '0 0 1 * *';

    /**
     * flush cache method
     *
     * @return bool
     */
    public function truncateMonologLogs(): bool
    {
        $logDir = \App\App::getDir(\App\App::ROOT) . DS . 'var' . DS . 'log';
        $logFile = $logDir . DS . 'logger.log';
        $rotatedFile = $logDir . DS . 'logger.' . date('Ymd') . '.log';
        
        if (!file_exists($logFile)) {
            return true;
        }

        if (!@rename($logFile, $rotatedFile)) {
            if (!@copy($logFile, $rotatedFile)) {
                return false;
            }
            @unlink($logFile);
        }

        if (!@touch($logFile)) {
            return false;
        }

        @chmod($logFile, 0644);

        if (file_exists($rotatedFile)) {
            if (filesize($rotatedFile) === 0) {
                @unlink($rotatedFile);
            } else {
                $this->getGZip()->compress($rotatedFile);
            }
        }

        return true;
    }
}
