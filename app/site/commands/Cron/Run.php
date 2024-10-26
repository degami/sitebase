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

namespace App\Site\Commands\Cron;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Cron\CronExpression;
use App\Site\Models\CronTask;
use App\Site\Models\CronLog;
use Exception;
use DateTime;
use Symfony\Component\Console\Command\Command;

/**
 * Run Cron Command
 */
class Run extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Run Cron');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $start_mtime = microtime(true);
        $start_time = new DateTime();
        $cron_executed = [];
        $this->getLog()->info('Cron run.');
        foreach (CronTask::getCollection()->where('schedule IS NOT NULL AND active = 1') as $task) {
            $cron = CronExpression::factory($task->getSchedule());
            if ($cron->isDue()) {
                try {
                    $this->containerCall(json_decode($task->getCronTaskCallable()));
                    $cron_executed[] = $task->getTitle();
                } catch (Exception $e) {
                    $this->getLog()->critical($e->getMessage() . "\n" . $e->getTraceAsString());
                }
            }
        }

        if (!empty($cron_executed)) {
            $cron_log = $this->containerMake(CronLog::class);
            $cron_log->run_time = $start_time->format('Y-m-d H:i:s');
            $cron_log->tasks = implode(',', $cron_executed);
            $cron_log->duration = (microtime(true) - $start_mtime);

            $cron_log->persist();
        }
        $this->getLog()->info('Cron end. executed tasks: '.count($cron_executed));

        return Command::SUCCESS;
    }
}
