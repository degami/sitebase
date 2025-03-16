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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Models\Website;
use App\App;
use App\Base\Abstracts\Commands\BaseExecCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Http Server Command
 */
class Serve extends BaseExecCommand
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
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $port = $input->getOption('port');
        if (!is_numeric($port) || $port < 1024) {
            $port = 8000;
        }

        /** @var Website $website */
        $website = $this->getAppWebsite();

        $this->getIo()->title("Serving [" . $website->getDomain() . "] pages on http://localhost:" . $port );
        $this->executeCommand("website_id=" . $website->getId() . " php -S localhost:" . $port . " " . App::getDir('root') . DS . 'php_server' . DS . 'router.php');

        return Command::SUCCESS;
    }
}
