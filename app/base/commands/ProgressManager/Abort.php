<?php

namespace App\Base\Commands\ProgressManager;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Models\ProgressManagerProcess;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Abort extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setDescription('Abort an active ProgressManagerProcess element')
            ->addArgument('process_id', InputArgument::REQUIRED, 'Process Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var ProgressManagerProcess $process */
            $process = ProgressManagerProcess::load($input->getArgument('process_id'));

            if (!$process->isRunning()) {
                throw new Exception("Process is not running");
            }

            $process->abort();
        }  catch (Exception $e) {
            $this->getIo()->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
