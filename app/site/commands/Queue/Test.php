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
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Test Queue Command
 */
class Test extends BaseCommand
{
    public const SLEEP_TIMEOUT = 500000; // 1/2 sec
    public const MAX_EXECUTIONS_NUMBER = 100000;
    public const LOCKFILE_NAME = "lock.queue";
    public const KILLFILE_NAME = "kill.queue";

    /**
     * @var int executions number
     */
    protected $executions = 0;

    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Test queue');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->getUtils()->addQueueMessage('test', []);
        $this->getIo()->success('Queued');

        return Command::SUCCESS;
    }
}
