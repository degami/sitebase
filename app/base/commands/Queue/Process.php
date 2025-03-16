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

namespace App\Base\Commands\Queue;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\App;
use App\Base\Models\QueueMessage;
use App\Base\Abstracts\Queues\BaseQueueWorker;
use App\Base\Exceptions\InvalidValueException;
use Exception;
use Symfony\Component\Console\Command\Command;

/**
 * Process Queue Command
 */
class Process extends BaseCommand
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
        $this->setDescription('Process queue')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('queue', null, InputOption::VALUE_OPTIONAL),
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
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $queue = $input->getOption('queue') ?? null;
        $lock_path = App::getDir(App::TMP) . DS . self::LOCKFILE_NAME;
        if (!file_exists($lock_path)) {
            @touch($lock_path);
        }
        if ($fp = fopen($lock_path, "r+")) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {  // acquire an exclusive, non blocking lock
                if (file_exists(App::getDir(App::TMP) . DS . self::KILLFILE_NAME)) {
                    @unlink(App::getDir(App::TMP) . DS . self::KILLFILE_NAME);
                }

                while (self::MAX_EXECUTIONS_NUMBER > $this->executions++) {
                    if (file_exists(App::getDir(App::TMP) . DS . self::KILLFILE_NAME)) {
                        $this->getLog()->log(Logger::INFO, "KILLFILE_NAME found.");
                        $this->executions = self::MAX_EXECUTIONS_NUMBER + 1;
                    }

                    try {
                        $message = $this->containerCall([QueueMessage::class, 'nextMessage'], ['queue_name' => $queue]);
                        if ($message instanceof QueueMessage) {
                            $worker_class = $message->getWorkerClass();

                            if (!is_subclass_of($worker_class, BaseQueueWorker::class)) {
                                throw new InvalidValueException($worker_class . " is not a QueueWorker", 1);
                            }
                            //$result =
                            $this->containerCall([$worker_class, 'process'], ['message' => $message]);
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                        $this->getUtils()->logException($e, static::class);
                    }

                    usleep(self::SLEEP_TIMEOUT);
                }

                $this->getLog()->log(Logger::INFO, "MAX_EXECUTIONS_NUMBER reached. Exiting");
                flock($fp, LOCK_UN);    // release the lock
            } else {
                $this->getIo()->error("Couldn't get the lock!");
            }

            fclose($fp);

            return Command::SUCCESS;
        }
    }
}
