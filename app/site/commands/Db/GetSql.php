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
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Psr\Container\ContainerInterface;
use \App\Base\Overrides\Migrations\Factory;
use \Genkgo\Migrations\Adapters\PdoMysqlAdapter;
use \Genkgo\Migrations\MigrationInterface;
use \App\App;

/**
 * Migrate Database Command
 */
class GetSql extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Get Create Tables SQL');
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
        foreach ($this->getSchema()->getTables() as $key => $table) {
            $output->writeln("");
            $output->writeln("-- Table ".$table->getName());
            $output->writeln("");

            $output->writeln($table->showCreate());
        }
    }
}
