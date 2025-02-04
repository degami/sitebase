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
     * @var string|array|null $query 
     */
    protected $query = null;

    /**
     * @var array|null
     */
    protected ?array $sort = null;

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
     * Ensures the Elasticsearch index exists, creating it if necessary.
     *
     * @return bool Returns true if the index exists or was created successfully, otherwise false.
     */
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
            'index' => self::INDEX_NAME,
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
                    '_index' => self::INDEX_NAME,
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

    /**
     * Counts the total number of documents matching the current query.
     *
     * @return int The count of documents matching the query.
     */
    public function countAll() : int
    {
        return $this->getClient()->count([
            'index' => self::INDEX_NAME,
            'body' => [
                "query" => $this->getQueryArray(),
            ],
        ])['count'];
    }

    /**
     * Performs a search query on the Elasticsearch index.
     *
     * @param int $page The page number to retrieve.
     * @param int $pageSize The number of results per page.
     * 
     * @return array An array containing the total count and the documents found.
     */
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
     * @return array|null The Elasticsearch query condition for the field, or `null` if the value is not recognized.
     */
    protected function buildCondition(string $field, mixed $value): ?array
    {
        if (is_object($value) && !($value instanceof \DateTime)) {
            throw new \InvalidArgumentException("value can't be an object");
        }

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