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
 * Restart Queue Command
 */
class Restart extends Command
{
    const KILLFILE_NAME = "kill.queue";
    const POLL_TIMEOUT = 500000; // 1/2 sec
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Restart queue');
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
        $kill_flag = App::getDir(App::TMP).DS.self::KILLFILE_NAME;
        if (!file_exists($kill_flag)) {
            @touch($kill_flag);
        }
        $output->writeln('<info>Waiting queue for restart</info>');
        while (file_exists($kill_flag)) {
            usleep(self::POLL_TIMEOUT);
        }
        $output->writeln('<info>Queue restarted</info>');
    }
}
