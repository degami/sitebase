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

namespace App\Base\Commands\App;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Show Routes Command
 */
class Routes extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Show Routes');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws InvalidValueException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $tableContents = [];

        foreach ($this->getRouters() as $routerName) {
            $router = $this->getService($routerName);

            if (!$this->containerCall([$router, 'isEnabled'])) {
                continue;
            }

            $tableContents[] = ['<info>'.$routerName.'</info>'];
            foreach ($router->getRoutes() as $group => $routes) {
                foreach ($routes as $route) {
                    $tableContents[] = [$route['name'], $group, $route['path'], $route['class'] . '::' . $route['method']];
                }
            }    
        }

        $this->renderTitle('Routes');
        $this->renderTable(['Name', 'Group', 'Path', 'Callable'], $tableContents);

        return Command::SUCCESS;
    }
}
