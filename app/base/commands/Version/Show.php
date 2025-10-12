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

namespace App\Base\Commands\Version;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Show Version Command
 */
class Show extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Version list');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {

        $stmt = $this->getPdo()->prepare("SELECT class_name, primary_key, COUNT(*) as count, GROUP_CONCAT(id) as ids FROM model_version GROUP BY class_name, primary_key ORDER BY class_name, primary_key");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->renderTitle('Versions');
        $this->renderTable(['Class Name', 'Primary Key', 'Count', 'ids'], array_map(function($row) {
            return [
                '<info>' . $row['class_name'] . '</info>',
                '<info>' . $row['primary_key'] . '</info>',
                $row['count'],
                $row['ids'],
            ];
        }, $rows));

        return Command::SUCCESS;
    }
}
