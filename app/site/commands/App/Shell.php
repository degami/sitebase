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

namespace App\Site\Commands\App;

use App\Base\Abstracts\Commands\BaseCommand;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use App\App;
use Exception;

/**
 * Application Shell Command
 */
class Shell extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Interactive Shell');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $app = $this->getContainer()->make(App::class);
        $this->getContainer()->set('app', $app);

        $this->getIo()->title('Welcome.');

        $history = [];
        do {
            $command = '';
            while (trim($command) == '') {
                $question = new Question("\n> ");
                $command = $helper->ask($input, $output, $question);
                $command = rtrim(trim($command), ';');
            }

            switch ($command) {
                case 'history':
                    $output->writeln('<info>History</info>');
                    foreach ($history as $key => $value) {
                        $output->writeln($value);
                    }
                    break;
                case 'quit':
                case 'exit':
                    $command = 'exit';
                    break;
                default:
                    try {
                        eval($command . ';');
                        $history[] = $command;
                    } catch (Exception $e) {
                        $output->writeln($e->getMessage());
                    }
                    break;
            }
        } while ($command != 'exit');
        $output->writeln('bye.');
    }
}
