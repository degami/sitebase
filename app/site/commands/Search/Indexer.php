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

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Tools\Plates\SiteBase;
use App\Site\Controllers\Frontend\Search;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Index data for search engine
 */
class Indexer extends BaseCommand
{
    public const SUMMARIZE_MAX_WORDS = 50;

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

        if (!$this->getSearch()->ensureIndex()) {
            $this->getIo()->error("Errors during index check");
            return Command::FAILURE;
        }

        $results = [];
        $classes = ClassFinder::getClassesInNamespace('App\Site\Models', ClassFinder::RECURSIVE_MODE);
        foreach ($classes as $modelClass) {
            if (is_subclass_of($modelClass, FrontendModel::class)) {
                /** @var FrontendModel $object */
                $type = basename(str_replace("\\", "/", strtolower($modelClass)));

                $fields_to_index = ['title', 'content'];
                if (method_exists($modelClass, 'exposeToIndexer')) {
                    $fields_to_index = $this->containerCall([$modelClass, 'exposeToIndexer']);
                }

                foreach ($this->containerCall([$modelClass, 'getCollection']) as $object) {
                    $body = [];

                    foreach (array_merge(['id', 'website_id', 'locale', 'created_at', 'updated_at'], $fields_to_index) as $field_name) {
                        $body[$field_name] = $object->getData($field_name);
                    }

                    $body_additional = [
                        'type' => $type,
                        'frontend_url' => $object->getFrontendUrl()
                    ];

                    if (in_array('content', $fields_to_index)) {
                        $body_additional['excerpt'] = $this->containerMake(SiteBase::class)->summarize($object->getContent(), self::SUMMARIZE_MAX_WORDS);
                    }

                    if (method_exists($object, 'additionalDataForIndexer')) {
                        $body_additional += $object->additionalDataForIndexer();
                    }

                    $response = $this->getSearch()->indexData($type . '_' . $object->getId(), array_merge($body, $body_additional));
                    if (!isset($results[$response['result']])) {
                        $results[$response['result']] = 0;
                    }
                    $results[$response['result']]++;
                }
            }
        }

        $this->renderTitle('Indexer results');
        $this->renderTable(array_keys($results), [$results]);
        
        return Command::SUCCESS;
    }


}
