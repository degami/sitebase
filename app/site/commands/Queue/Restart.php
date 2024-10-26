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

namespace App\Site\Commands\Queue;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\App;
use Symfony\Component\Console\Command\Command;

/**
 * Restart Queue Command
 */
class Restart extends BaseCommand
{
    public const KILLFILE_NAME = "kill.queue";
    public const POLL_TIMEOUT = 500000; // 1/2 sec

    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Restart queue');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $kill_flag = App::getDir(App::TMP) . DS . self::KILLFILE_NAME;
        if (!file_exists($kill_flag)) {
            @touch($kill_flag);
        }
        $output->writeln('<info>Waiting queue for restart</info>');
        while (file_exists($kill_flag)) {
            usleep(self::POLL_TIMEOUT);
        }
        $this->getIo()->success('Queue restarted');

        return Command::SUCCESS;
    }
}
