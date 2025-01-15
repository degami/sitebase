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

namespace App\Base\Tools\Search;

use App\Base\Abstracts\ContainerAwareObject;
use Elasticsearch\Client as ElasticSearchClient;

/**
 * Search Manager
 */
class Manager extends ContainerAwareObject
{
    public const INDEX_NAME = 'sitebase_index';
    public const RESULTS_PER_PAGE = 10;

    protected ?ElasticSearchClient $client = null;

    public function isEnabled() : bool
    {
        return $this->getEnv('ELASTICSEARCH', 0) != 0;
    }

    protected function getClient() : ElasticSearchClient
    {
        if (!$this->client) {
            $host = $this->getEnv('ELASTICSEARCH_HOST', 'localhost');
            $port = $this->getEnv('ELASTICSEARCH_PORT', '9200');
    
            $hosts = [
                $host.':'.$port,
            ];
    
            $this->client = \Elasticsearch\ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        }

        return $this->client;
    }

    public function ensureIndex() : bool
    {
        $client = $this->getClient();
        $params = [
            'index' => self::INDEX_NAME,
        ];

        if (@$client->indices()->exists($params)) {
            return true;
        }

        try {
            @$client->indices()->create($params);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function indexData(string $idx, array $data) : array
    {
        $params = [
            'index' => self::INDEX_NAME,
            'id' => $idx,
            'body' => $data,
        ];

        return $this->getClient()->index($params);
    }

    public function flush() : array 
    {
        return $this->getClient()->deleteByQuery([
            'index' => self::INDEX_NAME,
            'body' => [
                "query" => [
                    "query_string" => [
                        "query" => "*",
                    ],
                ],
            ],
        ]);
    }

    public function countAll() : int
    {
        return $this->getClient()->count([
            'index' => self::INDEX_NAME,
            'body' => [
                "query" => [
                    "query_string" => [
                        "query" => "*",
                    ],
                ],
            ],
        ])['count'];
    }

    public function search($query, $page = 0, $pageSize = self::RESULTS_PER_PAGE) : array
    {
        if (is_string($query)) {
            $query = [
                "query_string" => [
                    "query" => $query,
                ],
            ];
        }

        $search_result = $this->getClient()->search([
            'index' => self::INDEX_NAME,
            'body' => [
                'from' => $page * $pageSize,
                'size' => $pageSize,
                "query" => $query,
            ],
        ]);

        $total = $search_result['hits']['total']['value'] ?? 0;
        $hits = $search_result['hits']['hits'] ?? [];
        $docs = array_map(function ($el) {
            return $el['_source'];
        }, $hits);

        return ['total' => $total, 'docs' => $docs];
    }

    public function clientInfo() : array
    {
        return $this->getClient()->info();
    }
}