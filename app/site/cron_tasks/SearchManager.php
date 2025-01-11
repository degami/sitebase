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

namespace App\Site\Cron\Tasks;

use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Tools\Plates\SiteBase;
use App\Site\Commands\Search\Indexer;
use App\Site\Controllers\Frontend\Search;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Abstracts\ContainerAwareObject;

/**
 * Cron Search Related
 */
class SearchManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '20 3 * * *';

    /**
     * update search DB method
     *
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \Exception
     */
    public function updateSearchDB(): ?string
    {
        $client = $this->getElasticsearch();

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
                        $body_additional['excerpt'] = $this->containerMake(SiteBase::class)->summarize($object->getContent(), Indexer::SUMMARIZE_MAX_WORDS);
                    }

                    $params = [
                        'index' => Search::INDEX_NAME,
                        'id' => $type . '_' . $object->getId(),
                        'body' => array_merge($body, $body_additional),
                    ];

                    return $client->index($params);
                }
            }
        }
        return null;
    }
}
