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

/**
 * "Elasticsearch" Admin Page
 */
class Elasticsearch extends AdminPage
{
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
            $tableContents[] = ['Type' => $type, 'Count' => $count, 'actions' => null];
        }

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
}
