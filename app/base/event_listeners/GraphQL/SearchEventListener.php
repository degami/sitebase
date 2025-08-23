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

namespace App\Base\EventListeners\GraphQL;

use App\App;
use App\Base\Interfaces\EventListenerInterface;
use Gplanchat\EventManager\Event;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use App\Base\Exceptions\NotFoundException;
use GraphQL\Type\Definition\ResolveInfo;

class SearchEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'register_graphql_query_fields' => [$this, 'RegisterGraphQLQueryFields']
        ];
	}

    public function RegisterGraphQLQueryFields(Event $e) 
    {
        $app = App::getInstance();

        $object = $e->getData('object');
        $queryFields = &$object->queryFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;

        if (!isset($typesByName['ResultItem'])) {
            $typesByName['ResultItem'] = new ObjectType([
                'name' => 'ResultItem',
                'fields' => [
                    'frontend_url' => ['type' => Type::nonNull(Type::string())],
                    'title'        => ['type' => Type::nonNull(Type::string())],
                    'excerpt'      => ['type' => Type::nonNull(Type::string())],
                ]
            ]);
        }

        if (!isset($typesByName['SearchResult'])) {
            $typesByName['SearchResult'] = new ObjectType([
                'name' => 'SearchResult',
                'fields' => [
                    'search_query' => ['type' => Type::nonNull(Type::string())],
                    'search_result' => ['type' => Type::listOf($typesByName['ResultItem'])],
                    'total' => ['type' => Type::nonNull(Type::int())],
                    'page'  => ['type' => Type::nonNull(Type::int())],
                ]
            ]);
        }

        if (!isset($queryFields['search'])) {
            // search(input: String!, locale: String, page: Int): SearchResult
            $queryFields['search'] = [
                'type' => $typesByName['SearchResult'],
                'args' => [
                    'input' => ['type' => Type::nonNull(Type::string())],
                    'locale' => ['type' => Type::string()],
                    'page' => ['type' => Type::int()],
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                    $input = $args['input'];
                    $page = $args['page'] ?? 0;

                    $locale = $args['locale'] ?? $app->getCurrentLocale();

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
                },
            ];
        }
    }

    /**
     * gets search results based on query
     *
     * @param string|null $search_query
     * @param string|null $locale
     * @param int $page
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getSearchResult(?string $search_query = null, ?string $locale = null, int $page = 0): array
    {
        $app = App::getInstance();

        if ($search_query == null) {
            return ['total' => 0, 'docs' => []];
        }

        if ($locale == null) {
            $locale = $app->getCurrentLocale();
        }

        return $app->getSearch()
            ->addAndCondition('website_id', $app->getSiteData()->getCurrentWebsiteId())
            ->addAndCondition('locale', $locale)
            ->addOrCondition('content', ':match|'.$search_query)
            ->addOrCondition('title', ':match|'.$search_query)
            //->addOrCondition('date', ':match|'.$search_query) // cannot search on date field as format must be yyyy-MM-dd HH:mm:ss
            ->addSort('created_at', 'desc')
            ->addSort('id', 'asc')
            ->searchData($page);
    }
}