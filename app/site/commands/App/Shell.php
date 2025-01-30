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

namespace App\Site\Commands\App;

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Symfony\Component\Console\Command\Command;

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
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->getIo()->title('Welcome.');

        $app = App::getInstance();

        $history = [];
        do {
            $command = rtrim(trim($this->keepAsking("\n> ")), ';'); 
            switch ($command) {
                case 'history':
                    $this->renderTitle('History');
                    $this->getIo()->listing($history);
                    break;
                case 'quit':
                case 'exit':
                    $command = 'exit';
                    break;
                case 'help':
                    $this->renderHelp();
                    break;
                default:
                    try {
                        eval($command . ';');
                        $history[] = $command;
                    } catch (Throwable $e) {
                        $output->writeln($e->getMessage());
                        $output->writeln($e->getTraceAsString());
                    }
                    break;
            }
        } while ($command != 'exit');
        $output->writeln('bye.');

        return Command::SUCCESS;
    }

    protected function renderHelp()
    {
        $this->getIo()->writeln(
            "    ===============================================\n" .
            "                    PHP Interactive Shell\n" .
            "    ===============================================\n\n" .
            "    Welcome to the Interactive Shell.\n\n" .
            "    - You can execute any valid PHP code directly.\n" .
            "    - Use 'history' to view the list of previously executed commands.\n" .
            "    - Use 'quit' or 'exit' to leave the shell.\n" .
            "    - Use 'help' to display this message again.\n\n" .
            "    Current Variables:\n" .
            "    - \$this: The current command instance.\n" .
            "    - \$output: The output interface for writing messages.\n" .
            "    - \$input: The input interface for reading user input.\n" .
            "    - \$app: An application reference.\n" .
            "    ==============================================="
        );
    }
}
