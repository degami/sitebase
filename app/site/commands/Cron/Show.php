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
use App\Site\Models\CronLog;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Site\Models\CronTask;
use DateTime;

/**
 * Show Cron Command
 */
class Show extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Cron Tasks list');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("");
        $output->writeln($this->getLastHeartBeat());
        $output->writeln("");

        $tableContents = array_map(fn($cron) => [
            '<info>' . $cron->getId() . '</info>',
            $cron->getTitle(),
            $cron->getCronTaskCallable(),
            $cron->getSchedule(),
            $cron->getActive(),
        ], CronTask::getCollection()->getItems());

        $this->renderTable(['ID', 'Title', 'Callable', 'Schedule', 'Active'], $tableContents);
    }

    /**
     * gets last heart beat
     *
     * @return string
     * @throws Exception
     */
    protected function getLastHeartBeat(): string
    {
        $out = '<info>No heart beat run yet</info>';
        // SELECT * FROM `cron_log` WHERE 1 AND FIND_IN_SET('heartbeat_pulse', tasks) > 0 ORDER BY run_time DESC LIMIT 1
        /** @var CronLog $last_beat */
        $last_beat = $this->containerCall([CronLog::class, 'select'], ['options' => ['where' => ["1 AND FIND_IN_SET('heartbeat_pulse', tasks) > 0"], 'orderBy' => ['run_time DESC'], 'limitCount' => 1]])->fetch();

        if ($last_beat != null) {
            $interval = date_diff(new DateTime($last_beat['run_time']), new DateTime());
            $differenceFormat = '%y Year %m Month %d Day, %h Hours %i Minutes %s Seconds';
            $out = '<info>Last Beat on ' . $last_beat['run_time'] . ' (' . $interval->format($differenceFormat) . ' ago)</info>';
        }
        return $out;
    }
}
