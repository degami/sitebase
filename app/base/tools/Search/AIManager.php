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

use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Interfaces\AI\AIModelInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Sabre\VObject\Parser\Json;

/**
 * AI Search Manager (via llm embeddings)
 */
class AIManager extends Manager
{
    public function __construct(
        ContainerInterface $container, 
        protected AIModelInterface $llm,
        protected ?string $model = null
    ) {
        return parent::__construct($container);
        $this->model = $llm->getModel($this->model);
        $this->setIndexName(strtolower('llm_embeddings_' .  $llmName . '_' . $this->model));
    }

    /**
     * Returns index name
     * 
     * @return string index name
     */
    protected function getIndexName() : string
    {
        $llmName = static::getClassBaseName($this->llm::class);
        return strtolower('llm_embeddings_' .  $llmName . '_' . $this->model);
    }    

    /**
     * Ensures the Elasticsearch index exists, creating it if necessary.
     *
     * @return bool Returns true if the index exists or was created successfully, otherwise false.
     */
    public function ensureIndex(): bool
    {
        $client = $this->getClient();

        if (!$this->supportsKNN()) {
            throw new Exception("Server does not support knn_vector mappings");
        }

        try {
            if (@$client->indices()->exists(['index' => $this->getIndexName()])) {
                return true;
            }    

            $params = [
                'index' => $this->getIndexName(),
                'body'  => [
                    'settings' => [
                        'index' => [
                            'knn' => true // necessario per abilitare k-NN
                        ]
                    ],
                    'mappings' => [
                        'properties' => [
                            'embedding' => [
                                'type' => 'knn_vector',
                                'dimension' => $this->getEmbeddingDimensions(),
                            ],
                        ]
                    ]
                ]
            ];

            @$client->indices()->create($params);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Return embed field dimension, by performing a test
     * 
     * @return int
     */
    protected function getEmbeddingDimensions(): int
    {
        $testEmbedding = $this->llm->embed('test', $this->model) ?? [];
        return count($testEmbedding) ?: 1536;
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


        $body = [];

        foreach (['id', 'website_id', 'locale'] as $field_name) {
            $body[$field_name] = $object->getData($field_name);
        }
        $body['modelClass'] = $modelClass;

        $body['type'] = $type;

        $embeddable = $this->getEmbeddableDataForFrontendModel($object);

        $body['embedding'] = $this->llm->embed(implode(' ', array_filter($embeddable)), $this->model);

        return ['_id' => $type . '_' . $object->getId(), '_data' => $body];
    }

    public function getEmbeddableDataForFrontendModel(FrontendModel $object) : array
    {
        $modelClass = get_class($object);

        $fields_to_index = ['title', 'content'];
        if (method_exists($modelClass, 'exposeToIndexer')) {
            $fields_to_index = $this->containerCall([$modelClass, 'exposeToIndexer']);
        }

        $embeddable = [];
        foreach ($fields_to_index as $field_name) {
            $embeddable[$field_name] = $object->getData($field_name);
        }
        if (method_exists($object, 'additionalDataForIndexer')) {
            $embeddable += $object->additionalDataForIndexer();
        }

        $embeddable = array_map(function($el) {
            if (is_string($el)) {
                return strip_tags($el);
            }

            return null;
        }, $embeddable);

        return $embeddable;
    }

    /**
     * Search documents semantically similar to the given text.
     *
     * @param string $text The text to search for.
     * @param int $k Number of nearest neighbors to return.
     * @param array $filters Optional filters (e.g. ['locale' => 'it', 'website_id' => 1])
     *
     * @return array List of documents with _score
     */
    public function searchNearby(string $text, int $k = 5, array $filters = []): array
    {
        if (!$this->supportsKNN()) {
            throw new Exception("Server does not support knn_vector mappings");
        }

        $vector = $this->llm->embed($text, $this->model);

        $knnQuery = [
            'knn' => [
                'embedding' => [
                    'vector' => $vector,
                    'k' => $k * 2
                ]
            ]
        ];
/*
        if (!empty($filters)) {
            $filterClauses = [];
            foreach ($filters as $field => $value) {
                $filterClauses[] = ['term' => [$field => $value]];
            }

            $query = [
                'bool' => [
                    'filter' => $filterClauses,
                    'must'   => $knnQuery
                ]
            ];
        } else {
            $query = $knnQuery;
        }

        $params = [
            'index' => $this->getIndexName(),
            'body'  => [
                'size'  => $k,
                'query' => $query
            ]
        ];
*/
        foreach($filters as $boolType => $conditions) {
            foreach ((array)$conditions as $condition) {
                $this->addGenericCondition($condition, $boolType);
            }
        }

        $this->addGenericCondition($knnQuery, 'must');

        $response = $this->searchData();    

/*        
        $client = $this->getClient();
        $response = $client->search($params);

        $docs = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $docs[] = [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'data' => $hit['_source']
            ];
        }

        return [
            'total' => $response['hits']['total']['value'] ?? count($docs),
            'docs'  => $docs
        ];
*/

        return $response;
    }

    /**
     * Check if OpenSearch supports KNN (opensearch-knn or opensearch-ml plugin)
     *
     * @return bool
     */
    public function supportsKNN(): bool
    {
        try {
            // Recupera la lista dei plugin installati
            $plugins = $this->getClient()->cat()->plugins(['format' => 'json']);

            foreach ($plugins as $plugin) {
                if (isset($plugin['component']) &&
                    ($plugin['component'] === 'opensearch-knn' || $plugin['component'] === 'opensearch-ml')
                ) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // In caso di errore di connessione o risposta non valida
            return false;
        }

        return false;
    }
}