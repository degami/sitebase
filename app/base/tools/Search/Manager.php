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
use App\Base\Abstracts\Models\FrontendModel;
use Elasticsearch\Client as ElasticSearchClient;
use App\Base\Tools\Plates\SiteBase;
use App\Site\Commands\Search\Indexer;

/**
 * Search Manager
 */
class Manager extends ContainerAwareObject
{
    public const INDEX_NAME = 'sitebase_index';
    public const RESULTS_PER_PAGE = 10;
    public const SUMMARIZE_MAX_WORDS = 50;

    protected ?ElasticSearchClient $client = null;

    /**
     * @var string|array|null $query 
     */
    protected $query = null;

    /**
     * @var array|null
     */
    protected ?array $sort = null;

    public function isEnabled() : bool
    {
        return $this->getEnv('ELASTICSEARCH', 0) != 0;
    }

    protected function getClient() : ElasticSearchClient
    {
        if (is_null($this->client)) {
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

    public function getQuery() : string|array|null
    {
        return $this->query;
    }

    public function resetQuery() : static
    {
        $this->query = null;
        return $this;
    }

    public function setQuery(string|array $query) : static
    {
        if (is_array($query) && !isset($query['query_string']) && !isset($query['bool'])) {
            throw new \InvalidArgumentException("Invalid query format.");
        }

        $this->query = $query;
        return $this;
    }

    protected function getQueryArray() : array
    {
        if (is_string($this->query)) {
            return [
                "query_string" => [
                    "query" => $this->query,
                ],
            ];
        }

        if (is_array($this->query)) {
            return $this->query;
        }

        return [
            "query_string" => [
                "query" => "*",
            ],
        ];
    }

    public function ensureIndex(): bool
    {
        $client = $this->getClient();

        $params = [
            'index' => self::INDEX_NAME,
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'date' => [
                            'type' => 'date',
                            'format' => 'yyyy-MM-dd HH:mm:ss'
                        ],
                        'created_at' => [
                            'type' => 'date',
                            'format' => 'yyyy-MM-dd HH:mm:ss'
                        ],
                        'updated_at' => [
                            'type' => 'date',
                            'format' => 'yyyy-MM-dd HH:mm:ss'
                        ],
                        'location' => [ 
                            'type' => 'geo_point'
                        ],
                    ]
                ]
            ]
        ];

        try {
            if (@$client->indices()->exists(['index' => self::INDEX_NAME])) {
                return true;
            }    

            @$client->indices()->create($params);
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            return false;
        }

        return true;
    }

    public function getIndexDataForFrontendModel(FrontendModel $object) : array
    {
        $modelClass = get_class($object);
 
        $type = basename(str_replace("\\", "/", strtolower($modelClass)));

        $fields_to_index = ['title', 'content'];
        if (method_exists($modelClass, 'exposeToIndexer')) {
            $fields_to_index = $this->containerCall([$modelClass, 'exposeToIndexer']);
        }

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

        return ['id' => $type . '_' . $object->getId(), 'data' => array_merge($body, $body_additional)];
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

    public function bulkIndexData(array $items): array
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('The items array cannot be empty.');
        }
    
        $params = ['body' => []];
    
        foreach ($items as $item) {
            if (!isset($item['id'], $item['data']) || !is_array($item['data'])) {
                throw new \InvalidArgumentException('Each item must have an "id" and "data" array.');
            }
    
            $params['body'][] = [
                'index' => [
                    '_index' => self::INDEX_NAME,
                    '_id' => $item['id'],
                ],
            ];
    
            $params['body'][] = $item['data'];
        }
    
        try {
            $response = $this->getClient()->bulk($params);
    
            // Handle errors in the response, if any
            if (isset($response['errors']) && $response['errors']) {
                foreach ($response['items'] as $item) {
                    if (isset($item['index']['error'])) {
                        error_log('Error indexing item: ' . json_encode($item['index']['error']));
                    }
                }
            }
    
            return $response;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to perform bulk indexing: ' . $e->getMessage(), 0, $e);
        }
    }    

    public function flushIndex() : array 
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
                "query" => $this->getQueryArray(),
            ],
        ])['count'];
    }

    public function searchData($page = 0, $pageSize = self::RESULTS_PER_PAGE) : array
    {
        $searchParams = [
            'index' => self::INDEX_NAME,
            'body' => [
                'from' => $page * $pageSize,
                'size' => $pageSize,
                "query" => $this->getQueryArray(),
            ],
        ];

        if (is_array($this->sort)) {
            $searchParams['body']['sort'] = $this->sort;
        }

        $search_result = $this->getClient()->search($searchParams);

        $total = $search_result['hits']['total']['value'] ?? 0;
        $hits = $search_result['hits']['hits'] ?? [];
        $docs = array_map(function ($el) {
            return $el['_source'];
        }, $hits);

        return ['total' => $total, 'docs' => $docs];
    }

    public function addAndCondition(string $field, mixed $value): static
    {
        return $this->addCondition($field, $value, 'filter');
    }
    
    public function addNotCondition(string $field, mixed $value): static
    {
        return $this->addCondition($field, $value, 'must_not');
    }

    public function addOrCondition(string $field, mixed $value): static
    {
        if (!is_array($this->query)) {
            $this->query = ['bool' => []];
        }

        $this->query['bool']['minimum_should_match'] = 1;

        return $this->addCondition($field, $value, 'should');
    }

    public function addGenericCondition(array $condition, string $boolType) : static
    {
        $boolType = $this->getAdjustedBoolType($boolType, $condition);

        $this->query['bool'][$boolType][] = $condition;
        return $this;
    }

    public function resetSort() : static
    {
        $this->sort = null;
        return $this;
    }

    public function addSort(string $field, string $order = 'asc'): static
    {
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Sort order must be 'asc' or 'desc'.");
        }
    
        if (!is_array($this->sort)) {
            $this->sort = [];
        }
    
        $this->sort[] = [
            $field => [
                'order' => $order
            ]
        ];
    
        return $this;
    }

    protected function getAdjustedBoolType(string $boolType, array $condition) : string
    {
        if (!in_array($boolType, ['filter', 'must', 'must_not', 'should'])) {
            throw new \InvalidArgumentException("Invalid boolType format.");
        }

        if ($boolType === 'filter' && array_key_exists('match', $condition)) {
            return 'must';
        }

        return $boolType;
    }
    
    protected function addCondition(string $field, mixed $value, string $boolType): static
    {
        $condition = $this->buildCondition($field, $value);
    
        if (!$condition) {
            return $this;
        }

        $boolType = $this->getAdjustedBoolType($boolType, $condition);

        if (!is_array($this->query)) {
            $this->query = ['bool' => []];
        }

        if (!isset($this->query['bool'][$boolType])) {
            $this->query['bool'][$boolType] = [];
        }

        $this->query['bool'][$boolType][] = $condition;

        return $this;
    }

    protected function isSpatialValue($value) : bool
    {
        return is_array($value) && isset($value['lat']) && isset($value['lon']) && is_numeric($value['lat']) && is_numeric($value['lon']);
    }

    protected function buildCondition(string $field, mixed $value): ?array
    {
        if (is_array($value)) {
            if ($this->isSpatialValue($value) && isset($value['distance']) && $value['distance'] !== null) {
                return [
                    'geo_distance' => [
                        'distance' => $value['distance'],
                        $field => [
                            'lat' => $value['lat'],
                            'lon' => $value['lon'],
                        ],
                    ],
                ];
            }
            
            if (isset($value['top_left']) && isset($value['bottom_right']) && $this->isSpatialValue($value['top_left']) && $this->isSpatialValue($value['bottom_right'])) {
                return [
                    'geo_bounding_box' => [
                        $field => [
                            'top_left' => $value['top_left'],
                            'bottom_right' => $value['bottom_right'],
                        ],
                    ],
                ];
            }

            return [
                'terms' => [
                    $field => array_filter($value, 'is_scalar'),
                ],
            ];
        }
        
        if (is_string($value)) {
            switch (true) {
                case $value === ':isNull':
                    return [
                        'bool' => [
                            'must_not' => [
                                'exists' => [
                                    'field' => $field,
                                ],
                            ],
                        ],
                    ];

                case $value === ':isNotNull':
                    return [
                        'exists' => [
                            'field' => $field,
                        ],
                    ];

                case preg_match('/^:range(-?\d+(\.\d+)?)-(-?\d+(\.\d+)?)$/', $value, $matches):
                    $gte = is_numeric($matches[1]) ? (float)$matches[1] : null;
                    $lte = is_numeric($matches[3]) ? (float)$matches[3] : null;
    
                    $range = [];
                    if ($gte !== null) {
                        $range['gte'] = $gte;
                    }
                    if ($lte !== null) {
                        $range['lte'] = $lte;
                    }
    
                    return [
                        'range' => [
                            $field => $range,
                        ],
                    ];
    
                case preg_match('/^:lte(-?\d+(\.\d+)?)$/', $value, $matches):
                    return [
                        'range' => [
                            $field => [
                                'lte' => (float)$matches[1],
                            ],
                        ],
                    ];
    
                case preg_match('/^:gte(-?\d+(\.\d+)?)$/', $value, $matches):
                    return [
                        'range' => [
                            $field => [
                                'gte' => (float)$matches[1],
                            ],
                        ],
                    ];

                case preg_match('/^(.+)%$/', $value, $matches):
                    return [
                        'prefix' => [
                            $field => $matches[1],
                        ],
                    ];

                case preg_match('/^%.+%$/', $value) || preg_match('/^%(.+)$/', $value):
                    return [
                        'wildcard' => [
                            $field => str_replace('%', '*', $value),
                        ],
                    ];

                case preg_match('/^:fuzzy\|(.+)$/', $value, $matches):
                    return [
                        'fuzzy' => [
                            $field => $matches[1],
                        ],
                    ];
                case preg_match('/^:match\|(.+)$/', $value, $matches):
                    return [
                        'match' => [
                            $field => $matches[1],
                        ],
                    ];
            }
        }

        return [
            'term' => [
                $field => $value,
            ],
        ];
    }
    
    public function clientInfo() : array
    {
        return $this->getClient()->info();
    }

    public function __call(string $name, mixed $arguments) : mixed
    {
        return call_user_func_array([$this->getClient(), $name], $arguments);
    }
}