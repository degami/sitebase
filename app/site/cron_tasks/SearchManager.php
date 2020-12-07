<?php
/**
 * SiteBase
 * PHP Version 7.0
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
use App\Site\Controllers\Frontend\Search;
use HaydenPierce\ClassFinder\ClassFinder;
use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\ContainerAwareObject;

/**
 * Cron HeartBeat
 */
class SearchManager extends ContainerAwareObject
{
    const DEFAULT_SCHEDULE = '20 3 * * *';

    /**
     * class constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    /**
     * pulse method
     *
     * @return string
     */
    public function updateSearchDB()
    {
        $client = $this->getElasticsearch();

        $classes = ClassFinder::getClassesInNamespace('App\Site\Models', ClassFinder::RECURSIVE_MODE);
        foreach ($classes as $modelClass) {
            if (is_subclass_of($modelClass, FrontendModel::class)) {
                /** @var FrontendModel $object */
                $type = basename(str_replace("\\", "/", strtolower($modelClass)));

                $fields_to_index = ['title', 'content'];
                if (method_exists($modelClass, 'exposeToIndexer')) {
                    $fields_to_index = $this->getContainer()->call([$modelClass, 'exposeToIndexer']);
                }

                foreach ($this->getContainer()->call([$modelClass, 'all']) as $object) {
                    $body = [];

                    foreach (array_merge(['id', 'website_id', 'locale', 'created_at', 'updated_at'], $fields_to_index) as $field_name) {
                        $body[$field_name] = $object->getData($field_name);
                    }

                    $body_additional = [
                        'type' => $type,
                        'frontend_url' => $object->getFrontendUrl()
                    ];

                    if (in_array('content', $fields_to_index)) {
                        $body_additional['excerpt'] = $this->getContainer()->make(SiteBase::class)->summarize($object->getContent(), self::SUMMARIZE_MAX_WORDS);
                    }

                    $params = [
                        'index' => Search::INDEX_NAME,
                        'id' => $type . '_' . $object->getId(),
                        'body' => array_merge($body, $body_additional),
                    ];

                    $response = $client->index($params);
                }
            }
        }
    }
}
