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
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Controllers\AdminPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Configuration;
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

        $this->template_data += [
            'table_header' => ['Type' => '', 'Count' => '', 'actions' => null],
            'table_contents' => $tableContents,
            'total' => "Total documents: " . $count_result,
        ];

        return $this->template_data;
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Path' => ['order' => 'path', 'search' => 'path'],
            'Value' => null,
            'Is System' => 'is_system',
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($config) {
                return [
                    'ID' => $config->id,
                    'Website' => $config->getWebsiteId() == null ? $this->getUtils()->translate('All websites', locale: $this->getCurrentLocale()) : $config->getWebsite()->domain,
                    'Locale' => $config->getLocale() == null ? $this->getUtils()->translate('All languages', locale: $this->getCurrentLocale()) : $config->getLocale(),
                    'Path' => $config->path,
                    'Value' => $config->value,
                    'Is System' => $config->is_system ? $this->getHtmlRenderer()->getIcon('check') : '&nbsp;',
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($config->id),
                            (!$config->getIsSystem()) ? $this->getDeleteButton($config->id) : '',
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
