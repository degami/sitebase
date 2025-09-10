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

namespace App\Base\Commands\ProgressManager;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Models\ProgressManagerProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Progress Manager Watch Status Command
 */
class Watch extends BaseCommand
{
    /** @var ProgressBar[] */
    private array $bars = [];

    /** @var int[] */
    private $listedProcesses = [];

    protected function configure(): void
    {
        $this->setDescription('Monitor active ProgressManagerProcess elements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \RuntimeException("This command needs a ConsoleOutputInterface");
        }

        $section = $output->section();
        $output->writeln("<info>Monitor active processes (CTRL+C to exit)</info>");
        $output->writeln("");

        while (true) {
            $this->render($section);

//            usleep(100000); // 0.1 seconds

            usleep(1000000); // 1 second
        }

        return Command::SUCCESS;
    }

    private function render($section): void
    {
        $processes = ProgressManagerProcess::getCollection()
            ->orWhere('started_at IS NOT NULL AND ended_at IS NULL')
            ->addOrder(['started_at' => 'desc'])
            ->addOrder(['started_at' => 'asc']);
        
        if (!empty($this->listedProcesses)) {
            $processes->orWhere(['id' => $this->listedProcesses]);
        }

        $lines = [];
        foreach ($processes as $process) {
            if (!in_array($process->getId(), $this->listedProcesses)) {
                $this->listedProcesses[] = $process->getId();
            }

            $percent = $process->getProgressPercentual();
            $message = $process->getMessage() ?? '';

            $status = $process->getEndedAt() ? 'ended [' . match($process->getExitStatus()) {
                ProgressManagerProcess::ABORT   => '🛑 aborted',
                ProgressManagerProcess::INVALID => '⚠️ invalid',
                ProgressManagerProcess::FAILURE => '❌ failed',
                ProgressManagerProcess::SUCCESS => '✅ success',
            } . ']' : '⏳ active';

            $barWidth = 30;
            $filled = (int)round(($percent / 100) * $barWidth);
            $bar = str_repeat('=', $filled) . str_repeat(' ', $barWidth - $filled);

            $lines[] = sprintf(
                "ID:%-4s [%s] %3d%% %s | %s",
                $process->getId(),
                $bar,
                $percent,
                $status,
                $message
            );
        }

        if (empty($lines)) {
            $lines[] = "No active processes found.";
        }

        $section->clear();
        $section->writeln($lines);
    }
}
