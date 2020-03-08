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
namespace App\Site\Commands\Db;

use \App\Base\Abstracts\Commands\BaseCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use \Psr\Container\ContainerInterface;
use \Spatie\DbDumper\Databases\MySql;
use \Spatie\DbDumper\Compressors\GzipCompressor;
use \App\App;
use \Exception;

/**
 * Dump Database Command
 */
class Dump extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Database Dump');
    }

    /**
     * {@inheritdocs}
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        MySql::create()
            ->setDbName($this->getContainer()->get('dbname'))
            ->setUserName($this->getContainer()->get('dbuser'))
            ->setPassword($this->getContainer()->get('dbpass'))
        //            ->useSingleTransaction()
        //            ->skipLockTables()
            ->useCompressor(new GzipCompressor())
            ->dumpToFile(App::getDir(App::DUMPS).DS.'dump.'.date("Ymd_His").'.sql.gz');
    }
}
