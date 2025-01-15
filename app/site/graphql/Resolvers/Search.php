<?php

namespace App\Site\GraphQL\Resolvers;

use App\App;
use App\Base\Exceptions\NotFoundException;
use App\Base\Interfaces\GraphQl\ResolverInterface;

class Search implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        $input = $args['input'];
        $page = $args['page'] ?? 0;

        $locale = $args['locale'];

        if (!\App\App::getInstance()->getSearch()->isEnabled()) {
            throw new NotFoundException();
        }

        $search_result = static::getSearchResult($input, $locale, $page);
        $docs = $search_result['docs'];
        $total = $search_result['total'];

        return [
            'search_query' => $input,
            'search_result' => array_map(function ($el) {
                return [
                    'frontend_url' => $el['frontend_url'],
                    'title' => $el['title'],
                    'excerpt' => $el['excerpt'],
                ];
            }, $docs),
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
            return ['total' => 0, 'docs' => []];
        }

        if ($locale == null) {
            $locale = $app->getCurrentLocale();
        }

        return $app->getSearch()->search([
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
        ], $page);
    }
}