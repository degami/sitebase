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

namespace App\Base\Commands\Utils;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Models\Website;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Get Url Command
 */
class GetUrl extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Get route url')
            ->addArgument('route_name', InputArgument::OPTIONAL, 'Route name', 'frontend.root')
            ->addArgument('params', InputArgument::IS_ARRAY, 'Route parameters (key=value ...)', []);
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $route_name = $input->getArgument('route_name');

        if ($route_name == 'admin' || $route_name == 'frontend') {
            $route_name .= '.root';
        }

        $params = $input->getArgument('params');

        $route_params = [];
        foreach ($params as $param) {
            if (str_contains($param, '=')) {
                [$key, $value] = explode('=', $param, 2);
                $route_params[$key] = $value;
            }
        }

        // preload routers
        $this->getApp()->getRouters();

        /** @var Website $website */
        $website = $this->getAppWebsite();

        $this->getApp()->getEnvironment()->putVariable('BASE_URL', 'https://'.$website->getDomain());

        $router = $this->getApp()->getWebRouter();
        if (str_starts_with($route_name, 'admin.')) {
            $router = $this->getApp()->getAdminRouter();
        }

        $this->getIo()->writeln($router->getUrl($route_name, $route_params));
        return Command::SUCCESS;
    }
}
