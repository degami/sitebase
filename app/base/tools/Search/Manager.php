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
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\FrontendModel;
use Elasticsearch\Client as ElasticSearchClient;
use App\Base\Tools\Plates\SiteBase;
use InvalidArgumentException;
use App\Base\Models\ProgressManagerProcess;
use App\App;


/**
 * Search Manager
 */
class Manager extends ContainerAwareObject
{
    public const INDEX_NAME = 'sitebase_index';
    public const RESULTS_PER_PAGE = 10;
    public const SUMMARIZE_MAX_WORDS = 50;
    public const MAX_ELEMENTS_PER_QUERY = 10000;
    public const DEFAULT_SCROLL_TIME = '1m';

    protected ?ElasticSearchClient $client = null;

    /**
     * @var string|null
     */
    protected ?string $index = null;

    /**
     * @var string|array|null $query 
     */
    protected string|array|null $query = null;

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
        return $this->getEnvironment()->getVariable('ELASTICSEARCH', 0) != 0;
    }

    /**
     * Retrieves the Elasticsearch client instance.
     *
     * @return ElasticSearchClient Returns an instance of the Elasticsearch client.
     */
    protected function getClient() : ElasticSearchClient
    {
        if (is_null($this->client)) {
            $host = $this->getEnvironment()->getVariable('ELASTICSEARCH_HOST', 'localhost');
            $port = $this->getEnvironment()->getVariable('ELASTICSEARCH_PORT', '9200');
    
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
 
        $type = strtolower(static::getClassBasename($modelClass));

        $fields_to_index = ['title', 'content'];
        if (method_exists($modelClass, 'exposeToIndexer')) {
            $fields_to_index = $this->containerCall([$modelClass, 'exposeToIndexer']);
        }

        $body = [];

        foreach (array_merge(['id', 'website_id', 'locale', 'created_at', 'updated_at'], $fields_to_index) as $field_name) {
            $body[$field_name] = $object->getData($field_name);
        }
        $body['modelClass'] = $modelClass;
        
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
     * @param FrontendModel $object object to index
     * 
     * @return array The response from Elasticsearch after indexing.
     */
    public function indexFrontendModel(FrontendModel $object) : array
    {
        $indexData = $this->getIndexDataForFrontendModel($object);
        return $this->indexData($indexData['_id'], $indexData['_data']);
    }


    /**
     * indexes a frontend objects collection
     * 
     * @param BaseCollection $collection collection to index
     * 
     * @return array|null The response from Elasticsearch after indexing.
     */
    public function indexFrontendCollection(BaseCollection $collection) : ?array
    {
        $items = $collection->map(fn($object) => $this->getIndexDataForFrontendModel($object), $collection->getItems());
        if (empty($items)) {
            return null;
        }

        return $this->bulkIndexData($items);
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
    public function countAll(?string $aggregationName = null, $recursionLevel = 0) : int
    {
        if (!empty($this->aggregations)) {
            if ($recursionLevel == 0 && isset($this->aggregations[$aggregationName]['composite']['after'])) {
                unset($this->aggregations[$aggregationName]['composite']['after']);
            }

            $searchParams = [
                'index' => $this->getIndexName(),
                'body' => [
                    'size' => 0,
                    "query" => $this->getQueryArray(),
                    'aggs' => $this->getAggregationsArray(),
                ],
            ];

            $search_result = $this->getClient()->search($searchParams);

            if (!isset($search_result['aggregations'][$aggregationName])) {
                throw new InvalidArgumentException("aggregation $aggregationName not found");
            }

            if (isset($search_result['aggregations'][$aggregationName]['buckets'])) {
                $totalElems = count($search_result['aggregations'][$aggregationName]['buckets'] ?? []);

                if (isset($search_result['aggregations'][$aggregationName]['after_key'])) {
                    $this->aggregations[$aggregationName]['composite']['after'] = $search_result['aggregations'][$aggregationName]['after_key'];
                    $totalElems += $this->countAll($aggregationName, $recursionLevel + 1);
                }

                return $totalElems;
            }

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
     * @param string|null $withScroll Use Scroll api
     * 
     * @return array
     */
    public function getSearchParams(int $page = 0, int $pageSize = self::RESULTS_PER_PAGE, bool $onlyAggregations = false, ?string $withScroll = null) : array
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
            unset($searchParams['body']['from']);
            $searchParams['body']['aggs'] = $this->getAggregationsArray();
        } else {
            if (!$withScroll && (($page + 1) * $pageSize) > self::MAX_ELEMENTS_PER_QUERY) {
                throw new InvalidArgumentException('from + size cannot be over '.self::MAX_ELEMENTS_PER_QUERY);
            }

            if (is_array($this->getSource())) {
                $searchParams['body']['_source'] = $this->getSource();            
            }
    
            if (is_array($this->getSort())) {
                $searchParams['body']['sort'] = $this->getSort();
            }

            if ($withScroll && $searchParams['body']['size'] > 0) {
                $searchParams['scroll'] = $this->validateScrollTime($withScroll) ?? self::DEFAULT_SCROLL_TIME;
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
     * @param string|null $withScroll Use Scroll api
     * 
     * @return array An array containing the total count, the documents found and scroll_id if used, if aggregations are used, returns aggragations array
     */
    public function searchData(int $page = 0, int $pageSize = self::RESULTS_PER_PAGE, bool $onlyAggregations = false, ?string $withScroll = null) : array
    {
        // on page 0 no scroll is needed
        if ($page == 0) {
            $withScroll = null;
        }

        // if we are requesting more elements than the maximum per query , we need to use scroll
        if (!$withScroll && (($page + 1) * $pageSize) > self::MAX_ELEMENTS_PER_QUERY) {
            $withScroll = self::DEFAULT_SCROLL_TIME;
        }

        if ($withScroll) {
            try {
                $search_result = $this->openScroll($withScroll, $pageSize, $onlyAggregations);

                // continue to requested page
                for ($i = 1; $i <= $page; $i++) {
                    $search_result = $this->continueScroll($search_result['scroll_id'], $this->validateScrollTime($withScroll) ?? self::DEFAULT_SCROLL_TIME);
                }
            } finally {
                // close scroll
                $this->closeScroll($search_result['scroll_id']);
            }
        } else {
            // normal method
            $search_result = $this->getClient()->search($this->getSearchParams($page, $pageSize, $onlyAggregations, $withScroll));
        }

        if ($onlyAggregations) {
            return $this->normalizeAggregationsResults($search_result['aggregations']);
        }

        // $search_result can be direct ioensearch result or a current class function result . normalize 

        $total = $search_result['total'] ?? $search_result['hits']['total']['value'] ?? 0;
        if (isset($search_result['docs'])) {
            $docs = $search_result['docs'];
        } else {
            $hits = $search_result['hits']['hits'] ?? [];
            $docs = array_map(function ($el) {
                return $el['_source'];
            }, $hits);
        }

        $out = ['total' => $total, 'docs' => $docs];
        if ($withScroll) {
            $out['scroll_id'] = $search_result['scroll_id'] ?? $search_result['_scroll_id'];
        }
        if (isset($search_result['aggregations'])) {
            $out['aggregations'] = $search_result['aggregations'];
        }

        return $out;
    }

    /**
     * Opens a scroll search
     * 
     * @param int $pageSize The number of results per page.
     * @param string|null $withScroll Use Scroll api
     * @param bool $onlyAggregations Return only aggregations
    * 
     * @return array An array containing the total count, the documents found and scroll_id.
     */
    public function openScroll(string $withScroll, int $pageSize = self::RESULTS_PER_PAGE, bool $onlyAggregations = false) : array
    {
        $withScroll = $this->validateScrollTime($withScroll) ?? self::DEFAULT_SCROLL_TIME;
        $search_result = $this->getClient()->search($this->getSearchParams(0, $pageSize, $onlyAggregations, $withScroll));

        $total = $search_result['hits']['total']['value'] ?? 0;
        $hits = $search_result['hits']['hits'] ?? [];
        $docs = array_map(function ($el) {
            return $el['_source'];
        }, $hits);

        $out = ['total' => $total, 'docs' => $docs, 'scroll_id' => $search_result['_scroll_id']];
        if (isset($search_result['aggregations'])) {
            $out['aggregations'] = $search_result['aggregations'];
        }

        return $out;
    }

    /**
     * Continues scroll search
     * 
     * @param string $scrollId
     * @param string!null $crollTime
     * 
     * @return array An array containing the total count, the documents found and scroll_id.
     */
    public function continueScroll(string $scrollId, ?string $scrollTime = null) : array
    {
        $searchParams = [
            'scroll' => $this->validateScrollTime($scrollTime) ?? self::DEFAULT_SCROLL_TIME,
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

        $out = ['total' => $total, 'docs' => $docs, 'scroll_id' => $search_result['_scroll_id']];
        if (isset($search_result['aggregations'])) {
            $out['aggregations'] = $search_result['aggregations'];
        }

        return $out;
    }

    /**
     * Closes a scroll search
     * 
     * @param string $scrollId
     * 
     * @return array
     */
    public function closeScroll(string $scrollId) : array
    {
        return $this->getClient()->clearScroll(['scroll_id' => $scrollId]);
    }

    /**
     * Validates scroll time string
     * 
     * @return string|null
     */
    protected function validateScrollTime(?string $scrollTime) : ?string
    {
        if (is_null($scrollTime)) {
            return null;
        }

        $units = ['d', 'h', 'm', 's', 'ms', 'micros', 'nanos'];
        $pattern = '/^(\d+)(['.implode('|', $units).'])$/';

        if (!preg_match($pattern, $scrollTime)) {
            //throw new InvalidArgumentException('Invalid scroll time format. Use a number followed by a time unit (e.g., "1m", "2h").');
            return null;
        }

        return $scrollTime;
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
     * Continues aggregation search. only composite aggregations can be continued
     * 
     * @param string $aggregationName
     * @param mixed $after
     * 
     * @return array An array containing the total count, the documents found and scroll_id.
     */
    public function continueSearchAggregateDate(string $aggregationName, mixed $after) : array
    {
        if (!isset($this->aggregations[$aggregationName]['composite'])) {
            throw new InvalidArgumentException('Only composite aggregations can be continued');
        }

        $this->aggregations[$aggregationName]['composite']['after'] = $after;
        
        return $this->searchData(onlyAggregations: true);
    }

    /**
     * Normalizes the aggregation results from OpenSearch into a structured and simplified format.
     *
     * This function processes OpenSearch aggregations recursively, flattening nested aggregations
     * and extracting relevant data (e.g., `top_hits` values, bucket keys, counts, and `after_key`
     * for composite aggregations) into an easier-to-use structure.
     *
     * @param array $aggregations The raw aggregation results from OpenSearch.
     * @return array A normalized array with structured aggregation data.
     */
    protected function normalizeAggregationsResults(array $aggregations)
    {
        $structuredAggregations = [];

        foreach ($aggregations as $aggName => $aggData) {
            if (isset($aggData['buckets'])) {
                $structuredAggregations[$aggName] = [
                    'items' => [],
                    'total' => $aggData['doc_count'] ?? count($aggData['buckets']),
                ];

                foreach ($aggData['buckets'] as $bucket) {
                    $key = $bucket['key'];
                    $entry = [
                        'key' => $key,
                        'total' => $bucket['doc_count'] ?? count($bucket['buckets']),
                    ];

                    // Normalize sub-aggregations recursively
                    foreach ($bucket as $subAggName => $subAggData) {
                        if (!in_array($subAggName, ['key', 'doc_count'])) {
                            $entry[$subAggName] = $this->normalizeAggregationsResults([$subAggName => $subAggData])[$subAggName];
                        }
                    }

                    $structuredAggregations[$aggName]['items'][] = $entry;
                }

                // Include `after_key` for composite aggregations
                if (isset($aggData['after_key'])) {
                    $structuredAggregations[$aggName]['after_key'] = $aggData['after_key'];
                }
            } elseif (isset($aggData['hits']['hits'])) {
                // Normalize `top_hits`
                $structuredAggregations[$aggName] = array_map(
                    fn($hit) => $hit['_source'],
                    $aggData['hits']['hits']
                );
            } elseif (isset($aggData['value']) && count($aggData) === 1) {
                $structuredAggregations[$aggName] = $aggData['value'];
            } elseif (isset($aggData['values'])) {
                $structuredAggregations[$aggName] = $aggData['values'];
            } elseif (is_array($aggData)) {
                // Normalize recursively if it's an array
                $structuredAggregations[$aggName] = $this->normalizeAggregationsResults($aggData);
            } else {
                $structuredAggregations[$aggName] = $aggData;
            }
        }

        return $structuredAggregations;
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
        if (preg_match('/\./', $field)) {
            $condition = $this->buildNestedCondition($field, $value);
        } else {
            $condition = $this->buildCondition($field, $value);
        }

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

                case preg_match('/^%.+%$/', $value) || preg_match('/^%(.+)$/', $value):
                    return [
                        'wildcard' => [
                            $field => str_replace('%', '*', $value),
                        ],
                    ];

                case preg_match('/^(.+)%$/', $value, $matches):
                    return [
                        'prefix' => [
                            $field => $matches[1],
                        ],
                    ];

                case preg_match('/^:fuzzy\|(.+)$/', $value, $matches):
                    /*
                    return [
                        'fuzzy' => [
                            $field => $matches[1],
                        ],
                    ];
                    */
                    return [
                        'match' => [
                            $field => [
                                'query' => $matches[1],
                                'fuzzyness' => 'AUTO',
                            ],
                        ],
                    ];
                case preg_match('/^:match\|(.+)$/', $value, $matches):
                    return [
                        'match' => [
                            $field => $matches[1],
                        ],
                    ];
                case preg_match('/^:more_like_this\|(.+)$/', $value, $matches):
                    return [
                        'more_like_this' => [
                            'fields' => [$field],
                            'like' => $matches[1],
                            'min_term_freq' => 1,
                            'min_doc_freq' => 1,
                            'max_query_terms' => 12,
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
     * Builds a nested condition for an Elasticsearch/OpenSearch query.
     *
     * If the specified field contains a dot (.), it is treated as a nested field,
     * and a "nested" query is constructed. If the field is not nested, the condition
     * is delegated to `buildCondition()`.
     *
     * @param string $field The field name, potentially nested (e.g., "user.address.city").
     * @param mixed $value The value to be used for the condition.
     * @return array The array structure representing the nested query.
     */
    protected function buildNestedCondition(string $field, $value): array
    {
        $fieldParts = explode('.', $field);
    
        if (count($fieldParts) == 1) {
            return $this->buildCondition($field, $value);
        }
    
        $nestedPath = array_shift($fieldParts);
        $remainingField = implode('.', $fieldParts);
    
        return [
            'nested' => [
                'path' => $nestedPath,
                'query' => [
                    'bool' => [
                        'must' => [
                            $this->buildNestedCondition($remainingField, $value),
                        ]
                    ]
                ]
            ]
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
        } elseif (isset($condition['field']) && is_string($condition['field']) && array_key_exists('value', $condition)) {
            if (preg_match('/\./', $condition['field'])) {
                return $this->buildNestedCondition($condition['field'], $condition['value']);
            }
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
    public function buildAggregation(string $type, string|array|null $fields = null, ?string $script = null, ?array $scriptParams = null): array
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
    
    public static function indexFrontendClasses(ProgressManagerProcess $process, array $classes) : ?array
    {
        $classes = array_filter($classes, function($className) {
            return is_subclass_of($className, FrontendModel::class) && App::getInstance()->containerCall([$className, 'isIndexable']);
        });

        if (!count($classes)) {
            $process->invalud();
            return null;
        }

        $process->setTotal(count($classes))->persist();

        $results = [];
        foreach ($classes as $className) {
            $process->progress()->persist();
            $response = App::getInstance()->getSearch()->indexFrontendCollection(App::getInstance()->containerCall([$className, 'getCollection']));
            foreach (($response['items'] ?? []) as $item) {
                if (isset($item['index']['result'])) {
                    if (!isset($results[$item['index']['result']])) {
                        $results[$item['index']['result']] = 0;
                    }
                    $results[$item['index']['result']]++;
                }
            }
        }

        return $results;
    }
}