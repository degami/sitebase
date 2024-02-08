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

namespace App\Site\Commands\Search;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use App\Site\Controllers\Frontend\Search;

/**
 * Cache Statistics Command
 */
class Stats extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Search stats');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Search stats.</info>');

        $table = new Table($output);

        $client = $this->getElasticsearch();

        $count_result = $client->count([
            'index' => Search::INDEX_NAME,
            'body' => [
                "query" => [
                    "query_string" => [
                        "query" => "*",
                    ],
                ],
            ],
        ])['count'];

        $types = [];

        for ($i=0; $i<(intval($count_result / 1000)+1); $i++) {
            $search_result = $client->search([
                'index' => Search::INDEX_NAME,
                'body' => [
                    'from' => $i * 1000,
                    'size' => 1000,
                    "query" => [
                        "query_string" => [
                            "query" => "*",
                        ],
                    ],
                ],
            ]);
    
            $hits = $search_result['hits']['hits'] ?? [];
            $docs = array_map(function ($el) {
                return $el['_source'];
            }, $hits);
    
            foreach($docs as $doc) {
                $type = $doc['type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                } 
                $types[$type]++;
            }
        }

        $table
            ->addRow(
                [new TableCell(
                    "Total documents: " . $count_result,
                    ['colspan' => 2]
                )]
            );

        $table
            ->addRow(new TableSeparator())
            ->addRow(['<info>Type</info>', '<info>Count</info>']);

        foreach ($types as $type => $count) {
            $table
                ->addRow(new TableSeparator())
                ->addRow([$type, $count]);
        }

        $table->render();
    }
}
