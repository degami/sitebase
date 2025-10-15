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
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Test progressmanager (count to) Command
 */
class Test extends BaseCommand
{
    static $interrupted = false;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Test Progress Manager. This command only counts up to a number with a defined interval')
            ->addArgument('upTo', InputArgument::OPTIONAL, 'Count up to', 20)
            ->addOption('time', 't', InputOption::VALUE_OPTIONAL, 'sleep time', 5);
    }

        /**
     * {@inheritdoc}
     *
     * @return true
     */
    public static function registerCommand(): bool
    {
        return true;
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
        $upTo = $input->getArgument('upTo');
        $sleep = $input->getOption('time');

        /** @var ProgressManagerProcess $progressProcess */
        $progressProcess = ProgressManagerProcess::createForCallable([static::class, 'count'])->persist()->run($upTo, $sleep);

        return Command::SUCCESS;
    }

    public static function count(ProgressManagerProcess $process, int $countUpTo, int $sleep)
    {
        sleep(1);
        if ($countUpTo <= 0) {
            throw new Exception("wrong count up o value");
        }

        $process->setTotal($countUpTo)->persist();

        for ($i = 0; $i < $countUpTo; $i++) {
            $process->progress()->persist();
            echo $i."\n";
            sleep($sleep);
        }

    }
}
