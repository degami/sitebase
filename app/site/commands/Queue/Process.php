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
namespace App\Site\Commands\Queue;

use \App\Base\Abstracts\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Site\Models\QueueMessage;
use \App\Base\Abstracts\BaseQueueWorker;
use \App\Base\Exceptions\InvalidValueException;
use \Exception;

/**
 * Process Queue Command
 */
class Process extends Command
{
    const SLEEP_TIMEOUT = 500000; // 1/2 sec
    const MAX_EXECUTIONS_NUMBER = 100000;
    const LOCKFILE_NAME = "lock.queue";
    const KILLFILE_NAME = "kill.queue";

    /** @var integer executions number */
    protected $executions = 0;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Process queue')
        ->setDefinition(
            new InputDefinition([
                new InputOption('queue', null, InputOption::VALUE_OPTIONAL),
            ])
        );
    }

    /**
     * {@inheritdocs}
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $queue = $input->getOption('queue') ?? null;
        $lock_path = App::getDir(App::TMP).DS.self::LOCKFILE_NAME;
        if (!file_exists($lock_path)) {
            @touch($lock_path);
        }
        if ($fp = fopen($lock_path, "r+")) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {  // acquire an exclusive, non blocking lock
                if (file_exists(App::getDir(App::TMP).DS.self::KILLFILE_NAME)) {
                    @unlink(App::getDir(App::TMP).DS.self::KILLFILE_NAME);
                }

                while (self::MAX_EXECUTIONS_NUMBER > $this->executions++) {
                    if (file_exists(App::getDir(App::TMP).DS.self::KILLFILE_NAME)) {
                        $this->getLog()->log("KILLFILE_NAME found.");
                        $this->executions = self::MAX_EXECUTIONS_NUMBER + 1;
                    }

                    try {
                        $message = $this->getContainer()->call([QueueMessage::class, 'nextMessage'], ['queue_name' => $queue]);
                        if ($message instanceof QueueMessage) {
                            $worker_class = $message->getWorkerClass();
                            if (!is_subclass_of($worker_class, BaseQueueWorker::class)) {
                                throw new InvalidValueException($worker_class." is not a QueueWorker", 1);
                            }
                            $result = $this->getContainer()->call([$worker_class, 'process'], ['message' => $message]);
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                        $this->getUtils()->logException($e, static::class, false);
                    }

                    usleep(self::SLEEP_TIMEOUT);
                }

                $this->getLog()->log("MAX_EXECUTIONS_NUMBER reached. Exiting");
                flock($fp, LOCK_UN);    // release the lock
            } else {
                $io->error("Couldn't get the lock!");
            }

            fclose($fp);
        }
    }
}
