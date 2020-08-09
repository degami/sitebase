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
namespace App\Site\Commands\Db;

use \App\Base\Abstracts\Commands\BaseCommand;
use PDO;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use \Symfony\Component\Console\Helper\Table;
use \Exception;

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
     * {@inheritdocs}
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

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
                        $statement = $this->getDb()->query($command);
                        $query_type = trim(strtoupper(substr($statement->queryString, 0, strpos($statement->queryString, ' '))));

                        $statement->execute();
                        switch ($query_type) {
                            case 'SELECT':
                                $table = new Table($output);
                                $count = 0;
                                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                                    if ($count == 0) {
                                        $table->setHeaders(array_keys($row));
                                    }
                                    $table->addRow($row);
                                    $count++;
                                }
                                $table->render();
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
                        $output->writeln($e->getMessage());
                    }
                    break;
            }
        } while ($command != 'exit');
        $output->writeln('bye.');
    }
}
