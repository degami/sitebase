<?php

namespace App\Site\GraphQL\Resolvers;

use App\App;
use App\Base\Exceptions\NotFoundException;
use App\Base\GraphQl\ResolverInterface;

class Search implements ResolverInterface
{
    public const INDEX_NAME = 'sitebase_index';
    public const RESULTS_PER_PAGE = 10;

    public static function resolve(array $args): mixed
    {
        $input = $args['input'];
        $page = $args['page'] ?? 0;

        $locale = $args['locale'];

        if (!\App\App::getInstance()->getEnv('ELASTICSEARCH')) {
            throw new NotFoundException();
        }

        $search_result = static::getSearchResult($input, $locale, $page);

        $total = $search_result['hits']['total']['value'] ?? 0;
        $hits = $search_result['hits']['hits'] ?? [];


        return [
            'search_query' => $input,
            'search_result' => array_map(function ($el) {
                return [
                    'frontend_url' => $el['_source']['frontend_url'],
                    'title' => $el['_source']['title'],
                    'excerpt' => $el['_source']['excerpt'],
                ];
            }, $hits),
            'total' => $total,
            'page' => $page,
        ];
    }

    /**
     * gets search results based on query
     *
     * @param string|null $search_query
     * @param int $page
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getSearchResult($search_query = null, $locale = null, $page = 0): array
    {
        $app = App::getInstance();

        if ($search_query == null) {
            return [];
        }

        if ($locale == null) {
            $locale = $app->getCurrentLocale();
        }

        $client = $app->getElasticsearch();

        $params = [
            'index' => self::INDEX_NAME,
            'body' => [
                'from' => $page * self::RESULTS_PER_PAGE,
                'size' => self::RESULTS_PER_PAGE,
                'query' => [
                    "bool" => [
                        'minimum_should_match' => 1,
                        "should" => [
                            ['match' => ['content' => $search_query]],
                            ['match' => ['title' => $search_query]],
                            ['match' => ['date' => $search_query]],
                        ],
                        "filter" => [
                            ["term" => ["website_id" => $app->getSiteData()->getCurrentWebsiteId()]],
                            ["term" => ["locale" => $locale]],
                        ],
                    ],
                ],
            ]
        ];

        return $client->search($params);
    }
}