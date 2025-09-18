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

namespace App\Base\Commands\Search;

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Tools\Search\Manager as SearchManager;
use Symfony\Component\Console\Input\InputArgument;

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
        $this->setDescription('Search Statistics')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id');
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
        if (!$this->getSearch()->isEnabled()) {
            $this->getIo()->error('Elasticsearch is not enabled');
            return Command::FAILURE;
        }

        if (!$this->getSearch()->checkService()) {
            $this->getIo()->error('Service is not available');

            return Command::FAILURE;
        }

        $showType = $input->getArgument('type');
        $id = $input->getArgument('id');

        $count_result = $this->getSearch()->setQuery('*')->countAll();

        $types = [];

        $classes = array_filter(ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), fn($modelClass) => is_subclass_of($modelClass, FrontendModel::class));
        foreach ($classes as $className) {
            if (!$this->containerCall([$className, 'isIndexable'])) {
                continue;
            }
            
            $type = strtolower(static::getClassBasename($className));
            $types[$type] = 0;
        }

        for ($i=0; $i < (intval($count_result / SearchManager::MAX_ELEMENTS_PER_QUERY)+1); $i++) {
            $docs = $this->getSearch()->setQuery('*')->searchData($i, SearchManager::MAX_ELEMENTS_PER_QUERY)['docs'];
    
            foreach($docs as $doc) {
                $type = $doc['type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                } 
                $types[$type]++;
            }
        }

        if ($showType && !isset($types[$showType])) {
            $this->getIo()->error('Type not found');
            return Command::FAILURE;
        }

        if ($showType && $id) {
            $this->getSearch()->setQuery('type:"' . $showType . '" AND id:"' . $id . '"');

            if ($this->getSearch()->countAll() == 0) {
                $this->getIo()->error('Element ' . $id . ' not found');
                return Command::FAILURE;
            }

            foreach ($this->getSearch()->searchData(0, SearchManager::MAX_ELEMENTS_PER_QUERY)['docs'] as $doc) {
                $this->getIo()->writeln(json_encode($doc));
            }

            return Command::SUCCESS;
        } else if ($showType) {
            $this->getSearch()->setQuery('type:"' . $showType . '"');

            $tableContents = [['<info>id</info>','<info>website</info>','<info>locale</info>', '<info>title</info>']];
            foreach ($this->getSearch()->searchData(0, SearchManager::MAX_ELEMENTS_PER_QUERY)['docs'] as $doc) {
                $tableContents[] = [$doc['id'], $doc['website_id'], $doc['locale'], $doc['title']];
            }
        } else {
            $tableContents = [
                ["Total documents: " . $count_result],
                ['<info>Type</info>', '<info>Count</info>']
            ];

            foreach ($types as $type => $count) {
                $tableContents[] = [$type, $count];
            }
        }

        $this->renderTitle('Search stats');
        $this->renderTable([], $tableContents);

        return Command::SUCCESS;
    }
}
