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

use Degami\Basics\Exceptions\BasicException;
use Exception;
use App\Base\Abstracts\ContainerAwareObject;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Compressors\GzipCompressor;
use App\Base\Models\QueueMessage;
use App\App;

/**
 * Cache manager cron
 */
class DbManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '0 5 * * 0';

    /**
     * flush cache method
     *
     * @return bool
     */
    public function dumpDB(): bool
    {
        try {
            MySql::create()
                ->setDbName($this->getContainer()->get('dbname'))
                ->setUserName($this->getContainer()->get('dbuser'))
                ->setPassword($this->getContainer()->get('dbpass'))
                //            ->useSingleTransaction()
                //            ->skipLockTables()
                ->useCompressor(new GzipCompressor())
                ->dumpToFile(App::getDir(App::DUMPS) . DS . 'dump.' . date("Ymd_His") . '.sql.gz');

            return true;
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * remove cron logs older than 12 hours
     *
     * @return bool
     * @throws BasicException
     */
    public function dropOldCronLogs(): bool
    {
        $statement = $this->getDb()->query('DELETE FROM cron_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 HOUR)');
        $statement->execute();

        return true;
    }

    /**
     * remove processed queue messages older than 12 hours
     * @return bool
     * @throws BasicException
     */
    public function dropOldQueueMessages(): bool
    {
        $statement = $this->getDb()->query('DELETE FROM queue_message WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 HOUR) AND status = ' . $this->getDb()->quote(QueueMessage::STATUS_PROCESSED));
        $statement->execute();

        return true;
    }
}
