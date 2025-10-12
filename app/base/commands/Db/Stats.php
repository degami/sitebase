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

namespace App\Base\Commands\Db;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Batabase Statistics Command
 */
class Stats extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Db stats');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $tables = $this->getSchema()->preload()->getTables();
        $dbSize = 0;
        $tableContents = [
            ['<info>Table</info>', '<info>Rows</info>', '<info>Size</info>'],  
        ];
        foreach ($tables as $table) {
            $tableContents[] = [
                $table->getName(),
                $table->countRows(),
                $this->getUtils()->formatBytes($table->getSizeInBytes()),
            ];
            $dbSize += $table->getSizeInBytes();
        }

        $tableContents[] = [
            '<info>Total</info> ('.count($tables).' tables)',
            '',
            $this->getUtils()->formatBytes($dbSize),
        ];

        $this->renderTitle('Database stats');
        $this->renderTable([], $tableContents);

        return Command::SUCCESS;
    }
}
