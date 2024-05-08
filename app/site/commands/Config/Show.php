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

namespace App\Site\Commands\Config;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show Config Command
 */
class Show extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Show Config')
            ->setDefinition(
                new InputDefinition(
                    [
                        //new InputOption('website', null, InputOption::VALUE_OPTIONAL),
                    ]
                )
            );
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $website = $input->getOption('website');

        $condition = [];
        if (is_numeric($website)) {
            $condition = ['website_id' => $website];
        }

        $tableContents = array_map(fn($row) => [
            $row['id'],
            $row['website_id'],
            $row['path'],
            $row['value'],
            $row['is_system'] ? 'true' : 'false'
        ], Configuration::getCollection()->where($condition)->getItems());

        $this->renderTable(['Id', 'Website', 'Path', 'Value', 'System'], $tableContents);
    }
}
