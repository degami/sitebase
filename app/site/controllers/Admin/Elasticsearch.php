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

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Site\Controllers\Frontend\Search;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Site\Routing\RouteInfo;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Tools\Plates\SiteBase;
use Degami\Basics\Html\TagElement;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Elasticsearch" Admin Page
 */
class Elasticsearch extends AdminPage
{
    public const SUMMARIZE_MAX_WORDS = 50;

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'elasticsearch';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => $this->getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'search',
            'text' => 'Elasticsearch',
            'section' => 'system',
            'order' => 100,
        ];
    }

    function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null

    ) {
        parent::__construct($container, $request, $route_info);
        if (($this->getRequest()->query->get('action') ?? 'list') == 'list') {
            $this->addActionLink('reindex-btn', 'reindex-btn', $this->getHtmlRenderer()->getIcon('refresh-cw') . ' ' . $this->getUtils()->translate('Reindex', locale: $this->getCurrentLocale()), $this->getControllerUrl().'?action=reindex', 'btn btn-sm btn-warning');
        }    
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTemplateData(): array
    {
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


        $tableContents = [];

        foreach ($types as $type => $count) {
            $tableContents[] = [
                'Type' => $type, 
                'Count' => $count, 
                'actions' => implode("", [
                    $this->getActionButton('reindex', $type, 'secondary', 'refresh-cw', 'Reindex'),
                ])
            ];
        }

        usort($tableContents, fn ($a, $b) => $a['Type'] <=> $b['Type']);

        $clientInfo = $client->info();

        $this->template_data += [
            'table_header' => ['Type' => '', 'Count' => '', 'actions' => null],
            'table_contents' => $tableContents,
            'total' => "Total documents: " . $count_result,
            'info' => implode("\n", [
                $clientInfo['tagline'],
                $clientInfo['cluster_name'] . ' ' . $clientInfo['version']['number'],
            ]),
        ];

        return $this->template_data;
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender(): BasePage|Response
    {
        $out = parent::beforeRender();

        if ($this->getRequest()->get('action') == 'reindex') {
            $results = [];

            $classes = ClassFinder::getClassesInNamespace('App\Site\Models', ClassFinder::RECURSIVE_MODE);
            foreach ($classes as $modelClass) {

                $type = basename(str_replace("\\", "/", strtolower($modelClass)));
                if ($this->getRequest()->get('type') && $type != $this->getRequest()->get('type')) {
                    continue;
                }
    
                $reindexed = $this->reindexModels($modelClass);
                foreach ($reindexed as $type => $count) {
                    if (isset($results[$type])) {
                        $results[$type] += $count;
                    } else {
                        $results[$type] = $count;
                    }
                }
            }

            $total = 0;
            foreach($results as $type => $count) {
                $total += $count;
            }
            $this->addSuccessFlashMessage($this->getUtils()->translate("Reindexed %d elements", [$total]));

            return $this->refreshPage();
        }

        return $out;
    }

    protected function reindexModels(string $modelClass)
    {
        $client = $this->getElasticsearch();

        $results = [];
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

                $params = [
                    'index' => Search::INDEX_NAME,
                    'id' => $type . '_' . $object->getId(),
                    'body' => array_merge($body, $body_additional),
                ];

                $response = $client->index($params);
                if (!isset($results[$response['result']])) {
                    $results[$response['result']] = 0;
                }
                $results[$response['result']]++;
            }
        }

        return $results;
    }

    /**
     * gets action button html
     *
     * @param string $action
     * @param int $object_id
     * @param string $class
     * @param string $icon
     * @param string $title
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getActionButton(string $action, string $type, string $class, string $icon, string $title = ''): string
    {
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-' . $class,
                    'href' => $this->getControllerUrl() . '?action=' . $action . '&type=' . $type,
                    'title' => (trim($title) != '') ? $this->getUtils()->translate($title, locale: $this->getCurrentLocale()) : '',
                ],
                'text' => $this->getHtmlRenderer()->getIcon($icon),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {
        }

        return '';
    }
}
