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
use App\Base\Tools\Search\AIManager as AISearchManager;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Index embedding data for search engine
 */
class IndexerEmbeddings extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Index embedding data for search')
        ->addArgument('llm', InputArgument::OPTIONAL, 'LLM to user', 'googlegemini');
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

        /** @var AISearchManager $embeddingManager */
        $embeddingManager = $this->containerMake(AISearchManager::class, [
            'llm' => $this->getAI()->getAIModel($input->getArgument('llm')),
            'model' => match ($input->getArgument('llm')) {
                'googlegemini' => 'text-embedding-004',
                'chatgpt' => 'text-embedding-3-small',
                'claude' => 'claude-2.0-embedding',
                'groq' => 'groq-vector-1',
                'mistral' => 'mistral-embedding-001',
                'perplexity' => 'perplexity-embedding-001',
                default => null,
            }
        ]);


        if (!$embeddingManager->checkService()) {
            $this->getIo()->error('Service is not available');

            return Command::FAILURE;
        }

        if (!$embeddingManager->ensureIndex()) {
            $this->getIo()->error("Errors during index check");
            return Command::FAILURE;
        }

        $classes = array_merge(
            ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), 
            ClassFinder::getClassesInNamespace(App::BASE_MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE)
        );

        $classes = array_filter($classes, function($className) {
            return is_subclass_of($className, FrontendModel::class) && $this->containerCall([$className, 'isIndexable']);
        });

        if (!count($classes)) {
            $this->getIo()->error("No frontend classes found to index");
            return Command::FAILURE;
        }

        $results = [];
        foreach ($classes as $modelClass) {
            $response = $embeddingManager->indexFrontendCollection($this->containerCall([$modelClass, 'getCollection']));
            foreach (($response['items'] ?? []) as $item) {
                if (isset($item['index']['result'])) {
                    if (!isset($results[$item['index']['result']])) {
                        $results[$item['index']['result']] = 0;
                    }
                    $results[$item['index']['result']]++;
                }
            }
        }

        $this->renderTitle('Indexer results');
        $this->renderTable(array_keys($results), [$results]);
        
        return Command::SUCCESS;
    }


}
