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

namespace App\Base\Commands\Db;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use App\App;
use Symfony\Component\Console\Command\Command;

/**
 * Migrate Database Optionals Command
 */
class MigrateOptionals extends Migrate
{
    /**
     * {@inheritdoc}
     *
     * @param string|null $name
     * @param ContainerInterface|null $container
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        protected ContainerInterface $container,
        $name = null
    ) {
        parent::__construct($container, $name);
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

    /**
     * {@inheritdoc}
     * 
     * @return bool
     */
    public static function registerCommand(): bool
    {
        return App::dotEnvHasDbInformations();
    }
}
