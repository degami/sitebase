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
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Tools\Plates\SiteBase;
use App\Site\Controllers\Frontend\Search;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Index data for search engine
 */
class Flush extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Flush data from elasticsearch index');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getElasticsearch();

        $client->deleteByQuery([
            'index' => Search::INDEX_NAME,
            'body' => [
                "query" => [
                    "query_string" => [
                        "query" => "*",
                    ],
                ],
            ],
        ]);

        $output->writeln('<info>Data flushed</info>');
    }
}
