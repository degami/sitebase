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

use App\Base\Interfaces\EventListenerInterface;
use Gplanchat\EventManager\Event;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

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
            ];
        }
    }
}