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

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Psr\Container\ContainerInterface;
use \App\Site\Commands\Db\Migrate;
use \App\App;

/**
 * Migrate Database Optionals Command
 */
class MigrateOptionals extends Migrate
{
    /**
     * {@inheritdocs}
     * @param string                  $name
     * @param ContainerInterface|null $container
     */
    public function __construct($name = null, ContainerInterface $container = null)
    {
        parent::__construct($name, $container);
        $this->directory = App::getDir(App::MIGRATIONS).DS.'optionals';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Migrate Optionals Migrations (eg Fake data)');
    }
}
