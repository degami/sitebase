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

class PageRegionsEventListener implements EventListenerInterface
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


        if (!isset($typesByName['PageRegions'])) {
            $typesByName['PageRegions'] = new ObjectType([
                'name' => 'PageRegions',
                'fields' => [
                    'after_body_open' => ['type' => Type::string()],
                    'before_body_close' => ['type' => Type::string()],
                    'pre_menu' => ['type' => Type::string()],
                    'post_menu' => ['type' => Type::string()],
                    'pre_header' => ['type' => Type::string()],
                    'post_header' => ['type' => Type::string()],
                    'pre_content' => ['type' => Type::string()],
                    'post_content' => ['type' => Type::string()],
                    'pre_footer' => ['type' => Type::string()],
                    'post_footer' => ['type' => Type::string()],
                ]
            ]);
        }

        if (!isset($typesByName['PageRegionsResponse'])) {
            $typesByName['PageRegionsResponse'] = new ObjectType([
                'name' => 'PageRegionsResponse',
                'fields' => [
                    'locale' => ['type' => Type::string()],
                    'regions' => ['type' => $typesByName['PageRegions']],
                ]
            ]);
        }

        if (!isset($queryFields['translations'])) {
            //  pageRegions(rewrite_id: Int, route_path: String): PageRegionsResponse
            $queryFields['pageRegions'] = [
                'type' => $typesByName['PageRegionsResponse'],
                'args' => [
                    'rewrite_id' => ['type' => Type::int()],
                    'route_path' => ['type' => Type::string()],
                ],
            ];            
        }
    }
}