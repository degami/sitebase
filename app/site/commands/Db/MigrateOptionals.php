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

use Degami\Basics\Exceptions\BasicException;
use \Psr\Container\ContainerInterface;
use \App\App;

/**
 * Migrate Database Optionals Command
 */
class MigrateOptionals extends Migrate
{
    /**
     * {@inheritdocs}
     *
     * @param string|null $name
     * @param ContainerInterface|null $container
     * @throws BasicException
     */
    public function __construct($name = null, ContainerInterface $container = null)
    {
        parent::__construct($name, $container);
        $this->directory = App::getDir(App::MIGRATIONS) . DS . 'optionals';
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
