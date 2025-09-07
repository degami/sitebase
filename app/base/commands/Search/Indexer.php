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
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use App\Base\Models\ProgressManagerProcess;
use App\Base\Tools\Search\Manager as SearchManager;

/**
 * Index data for search engine
 */
class Indexer extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Index data for search');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \Exception
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

        if (!$this->getSearch()->ensureIndex()) {
            $this->getIo()->error("Errors during index check");
            return Command::FAILURE;
        }

        $classes = array_filter(ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), function($className) {
            return is_subclass_of($className, FrontendModel::class) && $this->containerCall([$className, 'isIndexable']);
        });

        if (!count($classes)) {
            $this->getIo()->error("No frontend classes found to index");
            return Command::FAILURE;
        }

        /** @var ProgressManagerProcess $progressManagerProcess */
        $progressManagerProcess = $this->containerMake(ProgressManagerProcess::class);
        $progressManagerProcess->setCallable(json_encode([SearchManager::class, 'indexFrontendClasses']));
        $results = $progressManagerProcess->run($classes);

        $this->renderTitle('Indexer results');
        $this->renderTable(array_keys($results), [$results]);
        
        return Command::SUCCESS;
    }


}
