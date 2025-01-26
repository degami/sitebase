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

namespace App\Site\Commands\Search;

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use HaydenPierce\ClassFinder\ClassFinder;

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
        $this->setDescription('Search Statistics');
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

        $count_result = $this->getSearch()->setQuery('*')->countAll();

        $types = [];

        $classes = array_filter(ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), fn($modelClass) => is_subclass_of($modelClass, FrontendModel::class));
        foreach ($classes as $className) {
            if (!$this->containerCall([$className, 'isIndexable'])) {
                continue;
            }
            $type = strtolower(basename(str_replace("\\", DS, $className)));
            $types[$type] = 0;
        }

        for ($i=0; $i < (intval($count_result / 1000)+1); $i++) {
            $docs = $this->getSearch()->setQuery('*')->searchData($i, 1000)['docs'];
    
            foreach($docs as $doc) {
                $type = $doc['type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                } 
                $types[$type]++;
            }
        }


        $tableContents = [
            ["Total documents: " . $count_result],
            ['<info>Type</info>', '<info>Count</info>']
        ];

        foreach ($types as $type => $count) {
            $tableContents[] = [$type, $count];
        }

        $this->renderTitle('Search stats');
        $this->renderTable([], $tableContents);

        return Command::SUCCESS;
    }
}
