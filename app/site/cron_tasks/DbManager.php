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
namespace App\Site\Cron\Tasks;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\ContainerAwareObject;
use \Spatie\DbDumper\Databases\MySql;
use \Spatie\DbDumper\Compressors\GzipCompressor;
use \App\App;

/**
 * Cache manager cron
 */
class DbManager extends ContainerAwareObject
{
    const DEFAULT_SCHEDULE = '0 5 * * 0';

    /**
     * class constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    /**
     * flush cache method
     *
     * @return boolean
     */
    public function dumpDB()
    {
        return MySql::create()
            ->setDbName($this->getContainer()->get('dbname'))
            ->setUserName($this->getContainer()->get('dbuser'))
            ->setPassword($this->getContainer()->get('dbpass'))
        //            ->useSingleTransaction()
        //            ->skipLockTables()
            ->useCompressor(new GzipCompressor())
            ->dumpToFile(App::getDir(App::DUMPS).DS.'dump.'.date("Ymd_His").'.sql.gz');
    }
}
