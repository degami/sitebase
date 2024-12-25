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

use App\Base\Abstracts\Commands\BaseCommand;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
use Symfony\Component\Console\Command\Command;

/**
 * Database Shell Command
 */
class Shell extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('DB Interactive Shell');
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
        $helper = $this->getHelper('question');

        $this->getIo()->title('Welcome.');

        $history = [];
        do {
            $command = rtrim(trim($this->keepAsking("\n> ")), ';');

            if (empty($command)) {
                $this->getIo()->warning("Empty command.");
                continue;
            }

            switch ($command) {
                case 'history':
                    $this->renderTitle('History');
                    $this->getIo()->listing($history);
                    break;
                case 'quit':
                case 'exit':
                    $command = 'exit';
                    break;
                default:
                    try {
                        $statement = $this->getDb()->query($command);
                        $query_type = trim(strtoupper(substr($statement->queryString, 0, strpos($statement->queryString, ' '))));

                        $statement->execute();
                        switch ($query_type) {
                            case 'SELECT':
                            case 'SHOW':
                                $tableContents = [];
                                $tableHeader = [];
                                $count = 0;
                                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                                    if ($count == 0) {
                                        $tableHeader = array_keys($row);
                                    }
                                    foreach($row as $key => $value) {
                                        if ($value === null) {
                                            $row[$key] = 'NULL';
                                        }
                                    }
                                    $tableContents[] = $row;
                                    $count++;
                                }
                                $this->renderTable($tableHeader, $tableContents);
                                break;
                            default:
                                if ($statement->columnCount() == 0) {
                                    // there is no result set, so the statement modifies rows
                                    $output->writeln(sprintf("Number of rows affected: %d", (int)$statement->rowCount()));
                                }
                                break;
                        }

                        $history[] = $command;
                    } catch (Exception $e) {
                        $this->getIo()->error($e->getMessage());
                    }
                    break;
            }
        } while ($command != 'exit');
        $output->writeln('bye.');

        return Command::SUCCESS;
    }
}
