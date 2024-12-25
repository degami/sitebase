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

namespace App\Site\Commands\Db;

use App\Base\Abstracts\Commands\BaseExecCommand;
use App\Base\Exceptions\NotFoundException;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\App;
use Symfony\Component\Console\Command\Command;

/**
 * Restore Dump Command
 */
class Restore extends BaseExecCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Restore Dump')
            ->addArgument('filename', InputArgument::REQUIRED, 'dump file path');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            if (!$this->commandExist('zcat') || !$this->commandExist('mysql')) {
                throw new NotFoundException('necessary commands (zcat, mysql) missing!');
            }

            $commandline = "zcat " . $input->getArgument('filename') . " | mysql -u" . $this->getEnv('DATABASE_USER') . " -p" . $this->getEnv('DATABASE_PASS') . " " . $this->getEnv('DATABASE_NAME');

            $this->executeCommand($commandline);
        } catch (NotFoundException $e) {
            if (!$this->commandExist('gunzip')) {
                throw new NotFoundException('necessary commands (gunzip) missing!');
            }

            $temporary_name = tempnam(App::getDir(App::TMP), 'gunzip');
            @copy($input->getArgument('filename'), $temporary_name.'.gz');
            @unlink($temporary_name);
            $commandline = "gunzip $temporary_name.gz";
            $this->executeCommand($commandline);

            $this->restore($temporary_name);
            //unlink($temporary_name);
            //unlink($temporary_name.".gz");
        } catch (BasicException $e) {
            $this->getIo()->error($e->getMessage());
        }

        $this->getIo()->writeln("dump restored");

        return Command::SUCCESS;
    }

    private function restore($filename)
    {
        // Temporary variable, used to store current query
        $query_str = '';
        // Read in entire file
        $lines = explode("\n", file_get_contents($filename));

        // Loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment
            if (substr($line, 0, 2) == '--' || $line == '') {
                continue;
            }

            // Add this line to the current segment
            $query_str .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';') {
                // Perform the query
                $this->getDb()->query($query_str);
                // Reset temp variable to empty
                $query_str = '';
            }
        }
    }
}
