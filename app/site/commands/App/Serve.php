<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Commands\App;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Site\Models\Website;
use App\App;

/**
 * Http Server Command
 * @package App\Site\Commands\App
 */
class Serve extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Run PHP Http Server')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('port', 'p', InputOption::VALUE_OPTIONAL),
                        //new InputOption('website', 'w', InputOption::VALUE_OPTIONAL),
                    ]
                )
            );
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $port = $input->getOption('port');
        if (!is_numeric($port) || $port < 1024) {
            $port = 8000;
        }

        /** @var Website $website */
        $website = $this->getAppWebsite();

        $this->getIo()->title("Serving [" . $website->getDomain() . "] pages on http://localhost:" . $port );
        system("website_id=" . $website->getId() . " php -S localhost:" . $port . " " . App::getDir('root') . DS . 'php_server' . DS . 'router.php');
    }
}
