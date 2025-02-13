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
use InvalidArgumentException;
use League\Plates\Template\Func;

/**
 * Search Manager
 */
class Manager extends ContainerAwareObject
{
    public const INDEX_NAME = 'sitebase_index';
    public const RESULTS_PER_PAGE = 10;
    public const SUMMARIZE_MAX_WORDS = 50;
    public const MAX_ELEMENTS_PER_QUERY = 10000;

    protected ?ElasticSearchClient $client = null;

    /**
     * @var string|null
     */
    protected ?string $index = null;

    /**
     * @var string|array|null $query 
     */
    protected $query = null;

    /**
     * @var array|null
     */
    protected ?array $sort = null;

    /**
     * @var array|null
     */
    protected ?array $aggregations = null;

    /**
     * @var array|null
     */
    protected ?array $source = null;

    /**
     * Checks if Elasticsearch is enabled.
     *
     * @return bool Returns true if Elasticsearch is enabled, otherwise false.
     */
    public function isEnabled() : bool
    {
        return $this->getEnv('ELASTICSEARCH', 0) != 0;
    }

    /**
     * Retrieves the Elasticsearch client instance.
     *
     * @return ElasticSearchClient Returns an instance of the Elasticsearch client.
     */
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

    /**
     * Checks elasticsearch service
     * 
     * @return bool
     */
    public function checkService() : bool
    {
        try {
            $client = $this->getClient();

            return @$client->ping();
        } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Gets the current query.
     *
     * @return string|array|null The current query, or null if no query is set.
     */
    public function getQuery() : string|array|null
    {
        return $this->query;
    }

    /**
     * Resets the query to null.
     *
     * @return static Returns the current instance.
     */
    public function resetQuery() : static
    {
        $this->query = null;
        return $this;
    }

    /**
     * Sets a query.
     *
     * @param string|array $query The query to set.
     * 
     * @throws InvalidArgumentException If the query format is invalid.
     * @return static Returns the current instance.
     */
    public function setQuery(string|array $query) : static
    {
        if (is_array($query) && !isset($query['query_string']) && !isset($query['bool'])) {
            throw new \InvalidArgumentException("Invalid query format.");
        }

        $this->query = $query;
        return $this;
    }

    /**
     * Converts the query into an array format.
     *
     * @return array Returns the query as an array.
     */
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

    /**
     * returns aggregations array
     * 
     * @return array
     */
    protected function getAggregationsArray() : array
    {
        return $this->aggregations ?? [];
    }

    /**
     * Sets index name
     * 
     * @return static Returns the current instance.
     */
    protected function setIndexName(string $indexName) : static
    {
        $this->index = $indexName;
        return $this;
    }

    /**
     * Returns index name
     * 
     * @return string index name
     */
    protected function getIndexName() : string
    {
        return $this->index ?? self::INDEX_NAME;
    }

    /**
     * Gets source part for search query
     * 
     * @return array|null
     */
    protected function getSource() : ?array
    {
        return $this->source;
    }

    /**
     * Sets source part for search query
     * 
     * @param array $source
     * @return static
     */
    public Function setSource(array $source) : static
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Ensures the Elasticsearch index exists, creating it if necessary.
     *
     * @return bool Returns true if the index exists or was created successfully, otherwise false.
     */
    public function ensureIndex(): bool
    {
        $client = $this->getClient();

        $params = [
            'index' => $this->getIndexName(),
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
            if (@$client->indices()->exists(['index' => $this->getIndexName()])) {
                return true;
            }    

            @$client->indices()->create($params);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Drops the elasticsearch index
     * 
     * @return static
     */
    public function dropIndex() : static
    {
        try {
            $client = $this->getClient();

            if (!@$client->indices()->exists(['index' => $this->getIndexName()])) {
                return $this;
            }    

            @$client->indices()->delete(['index' => $this->getIndexName()]);
        } catch (\Throwable $e) {}

        return $this;
    }

    /**
     * Prepares data from a frontend model to be indexed in Elasticsearch.
     *
     * @param FrontendModel $object The model instance containing the data to index.
     * 
     * @return array The data to be indexed.
     */
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

        return ['_id' => $type . '_' . $object->getId(), '_data' => array_merge($body, $body_additional)];
    }

    /**
     * indexes a frontend object
     * 
     * @param FrontendModel $object objet to index
     * 
     * @return array The response from Elasticsearch after indexing.
     */
    public function indexFrontendModel(FrontendModel $object) : array
    {
        $indexData = $this->getIndexDataForFrontendModel($object);
        return $this->indexData($indexData['_id'], $indexData['_data']);
    }

    /**
     * Indexes data into Elasticsearch.
     *
     * @param string $idx The index ID.
     * @param array $data The data to index.
     * 
     * @return array The response from Elasticsearch after indexing.
     */
    public function indexData(string $idx, array $data) : array
    {
        $params = [
            'index' => $this->getIndexName(),
            'id' => $idx,
            'body' => $data,
        ];

        return $this->getClient()->index($params);
    }

    /**
     * Indexes multiple data items in bulk.
     *
     * @param array $items An array of items to index, each containing '_id' and '_data' keys.
     * 
     * @throws InvalidArgumentException If an item is missing an '_id' or '_data' key.
     * @throws RuntimeException If the bulk indexing fails.
     * 
     * @return array The response from Elasticsearch after the bulk indexing operation.
     */
    public function bulkIndexData(array $items): array
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('The items array cannot be empty.');
        }
    
        $params = ['body' => []];
    
        foreach ($items as $item) {
            if (!isset($item['_id'], $item['_data']) || !is_array($item['_data'])) {
                throw new \InvalidArgumentException('Each item must have an "_id" and "_data" array.');
            }
    
            $params['body'][] = [
                'index' => [
                    '_index' => $this->getIndexName(),
                    '_id' => $item['_id'],
                ],
            ];
    
            $params['body'][] = $item['_data'];
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

    /**
     * Flushes all data in the Elasticsearch index.
     *
     * @return array The response from Elasticsearch after flushing the index.
     */
    public function flushIndex() : array 
    {
        return $this->getClient()->deleteByQuery([
            'index' => $this->getIndexName(),
            'body' => [
                "query" => [
                    "query_string" => [
                        "query" => "*",
                    ],
                ],
            ],
        ]);
    }

    /**
     * Counts the total number of documents matching the current query.
     *
     * @return int The count of documents matching the query.
     */
    public function countAll(?string $aggregationName = null) : int
    {
        if (!empty($this->aggregations)) {
            $searchParams = [
                'index' => $this->getIndexName(),
                'body' => [
                    'size' => 0,
                    "query" => $this->getQueryArray(),
                    'aggs' => $this->getAggregationsArray(),
                ],
            ];

            $search_result = $this->getClient()->search($searchParams);

            return $search_result['aggregations'][$aggregationName]['value'] ?? 0;
        }

        return $this->getClient()->count([
            'index' => $this->getIndexName(),
            'body' => [
                "query" => $this->getQueryArray(),
            ],
        ])['count'];
    }

    /**
     * Returns Search Params array
     *
     * @param int $page The page number to retrieve.
     * @param int $pageSize The number of results per page.
     * @param bool $onlyAggregations Return only aggregations
     * @param bool $withScroll Use Scroll api
     * 
     * @return array
     */
    public function getSearchParams(int $page = 0, int $pageSize = self::RESULTS_PER_PAGE, bool $onlyAggregations = false, bool $withScroll = false) : array
    {
        $searchParams = [
            'index' => $this->getIndexName(),
            'body' => [
                'from' => $page * $pageSize,
                'size' => $pageSize,
                "query" => $this->getQueryArray(),
            ],
        ];

        if ($onlyAggregations) {
            $searchParams['body']['size'] = 0;
            unset ($http_response_header['body']['from']);
            $searchParams['body']['aggs'] = $this->getAggregationsArray();
        } else {

            if (($page * $pageSize) + $pageSize > self::MAX_ELEMENTS_PER_QUERY) {
                throw new InvalidArgumentException('from + size cannot be over '.self::MAX_ELEMENTS_PER_QUERY);
            }

            if (is_array($this->getSource())) {
                $searchParams['body']['_source'] = $this->getSource();            
            }
    
            if (is_array($this->getSort())) {
                $searchParams['body']['sort'] = $this->getSort();
            }

            if ($withScroll && $searchParams['body']['size'] > 0) {
                $searchParams['scroll'] = '1m';
            }
        }

        return $searchParams;
    }

    /**
     * Performs a search query on the Elasticsearch index.
     *
     * @param int $page The page number to retrieve.
     * @param int $pageSize The number of results per page.
     * @param bool $onlyAggregations Return only aggregations
     * @param bool $withScroll Use Scroll api
     * 
     * @return array An array containing the total count, the documents found and scroll_id if used, if aggregations are used, returns aggragations array
     */
    public function searchData(int $page = 0, int $pageSize = self::RESULTS_PER_PAGE, bool $onlyAggregations = false, bool $withScroll = false) : array
    {
        $search_result = $this->getClient()->search($this->getSearchParams($page, $pageSize, $onlyAggregations, $withScroll));

        if ($onlyAggregations) {
            return $search_result['aggregations'];
        }

        $total = $search_result['hits']['total']['value'] ?? 0;
        $hits = $search_result['hits']['hits'] ?? [];
        $docs = array_map(function ($el) {
            return $el['_source'];
        }, $hits);

        $out = ['total' => $total, 'docs' => $docs];
        if ($withScroll) {
            $out['scroll_id'] = $search_result['_scroll_id'];
        }
        return $out;
    }

    /**
     * Continues scroll search
     * 
     * @param string $scrollId
     * 
     * @return array An array containing the total count, the documents found and scroll_id.
     */
    public function continueScroll(string $scrollId) : array
    {
        $searchParams = [
            'scroll' => '1m',
            'scroll_id' => $scrollId,
        ];

        $search_result = $this->getClient()->scroll($searchParams);

        if (isset($search_result['error'])) {
            throw new \Exception('Error in search query: '. json_encode($search_result));
        }

        $total = $search_result['hits']['total']['value'] ?? 0;
        $hits = $search_result['hits']['hits'] ?? [];
        $docs = array_map(function ($el) {
            return $el['_source'];
        }, $hits);

        return ['total' => $total, 'docs' => $docs, 'scroll_id' => $search_result['_scroll_id']];
    }

    /**
     * return aggregated data
     * 
     * @return array
     */
    public function searchAggregatedData() : array
    {
        return $this->searchData(onlyAggregations: true);
    }

    /**
     * Adds a condition for the "filter" clause of the Elasticsearch query.
     *
     * @param string|array $field The field to apply the condition to. if field parameter is an array, its value will be parsed as a condition and value parameter is ignored
     * @param mixed $value The value to match.
     * 
     * @return static Returns the current instance.
     */
    public function addAndCondition(string|array $field, mixed $value = null): static
    {
        if (is_array($field)) {
            return $this->addGenericCondition($this->parseCondition($field), 'filter');
        }
        return $this->addCondition($field, $value, 'filter');
    }
    
    /**
     * Adds a condition for the "must_not" clause of the Elasticsearch query.
     *
     * @param string|array $field The field to apply the condition to. if field parameter is an array, its value will be parsed as a condition and value parameter is ignored
     * @param mixed $value The value to exclude.
     * 
     * @return static Returns the current instance.
     */
    public function addNotCondition(string|array $field, mixed $value = null): static
    {
        if (is_array($field)) {
            return $this->addGenericCondition($this->parseCondition($field), 'must_not');
        }
        return $this->addCondition($field, $value, 'must_not');
    }

    /**
     * Adds a condition for the "should" clause of the Elasticsearch query.
     *
     * @param string|array $field The field to apply the condition to. if field parameter is an array, its value will be parsed as a condition and value parameter is ignored
     * @param mixed $value The value to match.
     * 
     * @return static Returns the current instance.
     */
    public function addOrCondition(string|array $field, mixed $value = null): static
    {
        if (!is_array($this->query)) {
            $this->query = ['bool' => []];
        }

        $this->query['bool']['minimum_should_match'] = 1;

        if (is_array($field)) {
            return $this->addGenericCondition($this->parseCondition($field), 'should');
        }
        return $this->addCondition($field, $value, 'should');
    }

    /**
     * Adds a generic condition to the query with a specified boolean type.
     *
     * @param array $condition The condition to add.
     * @param string $boolType The boolean type ('filter', 'must', 'must_not', 'should').
     * 
     * @return static Returns the current instance.
     */
    public function addGenericCondition(array $condition, string $boolType) : static
    {
        $boolType = $this->getAdjustedBoolType($boolType, $condition);

        $this->query['bool'][$boolType][] = $condition;
        return $this;
    }

    /**
     * Sets sorting array
     * 
     * @param array $sort Sort array
     * @return static
     */
    public function setSort(array $sort) : static
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * Gets sorting array
     * 
     * @return array|null
     */
    public function getSort() : ?array
    {
        return $this->sort;
    }

    /**
     * Resets the sorting for the query.
     *
     * @return static Returns the current instance.
     */
    public function resetSort() : static
    {
        $this->sort = null;
        return $this;
    }

    /**
     * Adds sorting to the query.
     *
     * @param string $field The field to sort by.
     * @param string $order The order of sorting ('asc' or 'desc').
     * @param array $additionalSortParams additional parameters
     * 
     * @throws InvalidArgumentException If the order is invalid.
     * 
     * @return static Returns the current instance.
     */
    public function addSort(string $field, string $order = 'asc', array $additionalSortParams = []): static
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
            ] + $additionalSortParams
        ];
    
        return $this;
    }

    /**
     * Adds an aggregation 
     *
     * @param string $name Aggregation name
     * @param array $aggregation Aggregation definition
     * 
     * @return static Returns the current instance.
     */
    public function addAggregation(string $name, array $aggregation): static
    {
        if (empty($aggregation)) {
            return $this;
        }

        if (!is_array($this->aggregations)) {
            $this->aggregations = [];
        }

        $this->aggregations[$name] = $aggregation;

        return $this;
    }

    /**
     * Resets aggregations
     * 
     * @return static Returns the current instance.
     */
    public function resetAggregations() : static
    {
        $this->aggregations = null;

        return $this;
    }

    /**
     * Adjusts the boolean type of a condition based on its contents.
     *
     * @param string $boolType The boolean type ('filter', 'must', 'must_not', 'should').
     * @param array $condition The condition to adjust.
     * 
     * @throws InvalidArgumentException If the boolean type is invalid.
     * 
     * @return string The adjusted boolean type.
     */
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
    
    /**
     * Adds a condition to the query.
     *
     * @param string $field The field to apply the condition to.
     * @param mixed $value The value to match.
     * @param string $boolType The boolean type ('filter', 'must', 'must_not', 'should').
     * 
     * @return static Returns the current instance.
     */
    protected function addCondition(string $field, mixed $value, string $boolType): static
    {
        $condition = $this->buildCondition($field, $value);

        if (empty($condition)) {
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

    /**
     * Checks if a value is a spatial value (latitude and longitude).
     *
     * @param mixed $value The value to check.
     * 
     * @return bool Returns true if the value is a valid spatial value, otherwise false.
     */
    protected function isSpatialValue($value) : bool
    {
        return is_array($value) && isset($value['lat']) && isset($value['lon']) && is_numeric($value['lat']) && is_numeric($value['lon']);
    }

    /**
     * Checks if a value is a range value (from and to).
     *
     * @param mixed $value The value to check.
     * 
     * @return bool Returns true if the value is a valid range value, otherwise false.
     */
    protected function isRangeValue($value) : bool
    {
        return is_array($value) && (isset($value['from']) || isset($value['to']));
    }

    /**
     * Build a condition for an Elasticsearch query based on a field and its value.
     *
     * The `$value` can have different formats depending on the type of query to be built:
     *
     * - **Array**:
     *     - **Spatial Queries**: If `$value` is an array containing `lat`, `lon`, and `distance`, a **geo_distance** query is built.
     *     - **Bounding Box**: If `$value` contains `top_left` and `bottom_right` (both of which must be spatial values), a **geo_bounding_box** query is built.
     *     - **Terms Query**: If `$value` is an array of scalars, a **terms** query is built.
     *
     * - **String**:
     *     - `:isNull`: A query to match documents where the field does not exist.
     *     - `:isNotNull`: A query to match documents where the field exists.
     *     - `:range(min-max)`: A range query where the field value must fall within the specified `min` and `max` range.
     *     - `:lte(value)`: A range query where the field value must be less than or equal to the specified `value`.
     *     - `:gte(value)`: A range query where the field value must be greater than or equal to the specified `value`.
     *     - `prefix(value%)`: A prefix query for values that begin with `value`.
     *     - `wildcard(%value%)`: A wildcard query for values matching the pattern `value`, with `%` as a wildcard character.
     *     - `:fuzzy|value`: A fuzzy query for a term with a tolerance for misspellings or variations.
     *     - `:match|value`: A match query for exact or analyzed text search.
     *
     * - **Other types (e.g., integers, booleans)**:
     *     - A **term** query is built for the field and the exact value.
     *
     * @param string $field The field to build the condition for.
     * @param mixed $value The value that will be used in the condition. The type and structure of `$value` determine the type of query to build.
     *
     * @throws InvalidArgumentException If the value type is invalid.
     * 
     * @return array The Elasticsearch query condition for the field.
     */
    protected function buildCondition(string $field, mixed $value): array
    {
        if (is_object($value) && !($value instanceof \DateTime)) {
            throw new \InvalidArgumentException("value can't be an object");
        }

        if (is_array($value)) {
            if ($this->isRangeValue($value)) {
                $range = [];
                if (isset($value['from']) && ($value['from'] instanceof \DateTime)) {
                    $value['from'] = $value['from']->format('Y-m-d H:i:s');
                    $range['format'] = 'yyyy-MM-dd HH:mm:ss||epoch_millis';
                }
                if (isset($value['to']) && ($value['to'] instanceof \DateTime)) {
                    $value['to'] = $value['to']->format('Y-m-d H:i:s');
                    $range['format'] = 'yyyy-MM-dd HH:mm:ss||epoch_millis';
                }

                if (isset($value['from'])) {
                    $range['gte'] = $value['from'];
                }
                if (isset($value['to'])) {
                    $range['lte'] = $value['to'];
                }

                return [
                    'range' => [
                        $field => $range,
                    ],
                ];
            }

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

                case preg_match('/^:lastDays\|(\d+)$/', $value, $matches):
                    return [
                        'range' => [
                            $field => [
                                'gte' => 'now-'.$matches[1].'d/d',
                                'lte' => 'now/d',
                            ],
                        ],
                    ];
                
                case preg_match('/^:lastHours\|(\d+)$/', $value, $matches):
                    return [
                        'range' => [
                            $field => [
                                'gte' => 'now-'.$matches[1].'h/h',
                                'lte' => 'now/h',
                            ],
                        ],
                    ];

                case preg_match('/^:nextDays\|(\d+)$/', $value, $matches):
                    return [
                        'range' => [
                            $field => [
                                'gte' => 'now/d',
                                'lte' => 'now+'.$matches[1].'d/d',
                            ],
                        ],
                    ];
                
                case preg_match('/^:nextHours\|(\d+)$/', $value, $matches):
                    return [
                        'range' => [
                            $field => [
                                'gte' => 'now/h',
                                'lte' => 'now+'.$matches[1].'h/h',
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

        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return [
            'term' => [
                $field => $value,
            ],
        ];
    }
    
    /**
     * Builds a script-based condition for Elasticsearch queries.
     *
     * @param string $script The script source code.
     * @param array $params Optional parameters for the script.
     * @return array The Elasticsearch script condition array.
     */
    protected function buildScriptCondition(string $script, array $params = []): array
    {
        return [
            'script' => [
                'script' => [
                    'source' => $script,
                    'params' => $params
                ]
            ]
        ];
    }

    /**
     * Parses a condition and determines whether it should be processed as a normal or script condition.
     *
     * @param array $condition The condition to parse.
     * @return array The Elasticsearch condition array.
     * @throws \InvalidArgumentException If the condition format is invalid.
     */
    protected function parseCondition(array $condition): array
    {
        if (isset($condition['script'])) {
            return $this->buildScriptCondition($condition['script'], $condition['params'] ?? []);
        } elseif (isset($condition['field']) && array_key_exists('value', $condition)) {
            return $this->buildCondition($condition['field'], $condition['value']);
        }

        throw new \InvalidArgumentException("Invalid condition format");
    }

    /**
     * Builds an OR group for Elasticsearch queries.
     *
     * Each condition must be an array in the format:
     * ['field' => '<field_name>', 'value' => 'a valid value for $value in buildCondition']
     * or a script condition array returned by buildScriptCondition().
     *
     * @param array $conditions Array of conditions to be combined with OR.
     * @return array The Elasticsearch OR condition array.
     */
    protected function buildOrGroup(array $conditions): array
    {
        return [
            'bool' => [
                'should' => array_map([$this, 'parseCondition'], $conditions),
                'minimum_should_match' => 1
            ]
        ];
    }

    /**
     * Builds an AND group for Elasticsearch queries.
     *
     * Each condition must be an array in the format:
     * ['field' => '<field_name>', 'value' => 'a valid value for $value in buildCondition']
     * or a script condition array returned by buildScriptCondition().
     *
     * @param array $conditions Array of conditions to be combined with AND.
     * @return array The Elasticsearch AND condition array.
     */
    protected function buildAndGroup(array $conditions): array
    {
        return [
            'bool' => [
                'must' => array_map([$this, 'parseCondition'], $conditions)
            ]
        ];
    }

    /**
     * Builds a NOT group for Elasticsearch queries.
     *
     * Each condition must be an array in the format:
     * ['field' => '<field_name>', 'value' => 'a valid value for $value in buildCondition']
     * or a script condition array returned by buildScriptCondition().
     *
     * @param array $conditions Array of conditions to be negated.
     * @return array The Elasticsearch NOT condition array.
     */
    protected function buildNotGroup(array $conditions): array
    {
        return [
            'bool' => [
                'must_not' => array_map([$this, 'parseCondition'], $conditions)
            ]
        ];
    }

    /**
     * Builds an aggregation.
     *
     * @param string $type Type of aggregation (eg. "terms", "avg", "sum", "min", "max", "value_count", "cardinality")
     * @param string|array|null $fields Field(s) to aggregeate on
     * @param string|null $script Optional script for aggregation
     * @return array Aggregation definition
     */
    public function buildAggregation(string $type, $fields = null, ?string $script = null, ?array $scriptParams = null): array
    {
        $validAggregations = [
            'avg', 'sum', 'min', 'max', 'value_count', 'cardinality', 'percentiles',
            'terms', 'multi_terms', 'histogram', 'date_histogram', 'range', 'date_range',
            'filters', 'nested', 'reverse_nested'
        ];

        if (!in_array($type, $validAggregations, true)) {
            throw new \InvalidArgumentException("Invalid aggregation type: '{$type}'.");
        }
        
        $aggregation = [];

        if ($script !== null) {
            $aggregation[$type] = ['script' => [
                'source' => $script,
                'params' => $scriptParams ?? [],
            ]];
        } elseif (is_array($fields)) {
            if ($type === 'terms') {
                $aggregation['multi_terms'] = ['terms' => array_map(fn($field) => ['field' => $field], $fields)];
            } else {
                throw new \InvalidArgumentException("Aggregation '{$type}' does not support multiple fields.");
            }
        } elseif ($fields !== null) {
            $aggregation[$type] = ['field' => $fields];
        } else {
            throw new \InvalidArgumentException("You must specify aggregation fields or script");
        }

        return $aggregation;
    }

    /**
     * Retrieves information about the Elasticsearch client.
     *
     * @return array The information returned by the Elasticsearch client.
     */
    public function clientInfo() : array
    {
        return $this->getClient()->info();
    }

    /**
     * Handles dynamic method calls to the Elasticsearch client.
     *
     * @param string $name The name of the method being called.
     * @param mixed $arguments The arguments passed to the method.
     * 
     * @return mixed The result of the method call.
     */
    public function __call(string $name, mixed $arguments) : mixed
    {
        return call_user_func_array([$this->getClient(), $name], $arguments);
    }
}