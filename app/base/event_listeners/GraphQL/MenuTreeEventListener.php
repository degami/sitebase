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
use GraphQL\Type\Definition\ResolveInfo;

class MenuTreeEventListener implements EventListenerInterface
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

        if (!isset($typesByName['menuTree'])) {
            //  menuTree(menu_name: String!, website_id: Int!): [Menu]
            $queryFields['menuTree'] = [
                'args' => [
                    'menu_name' => ['type' => Type::nonNull(Type::string())], 
                    'website_id' => ['type' => Type::nonNull(Type::int())]
                ],
                'type' => Type::listOf($typesByName['Menu']),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                    $locale = $args['locale'] ?? $app->getCurrentLocale();
                    if ($locale == null) {
                        $locale = $app->getSiteData()->getDefaultLocale();
                    }

                    $website_id = $args['website_id'];
                    $menu_name = $args['menu_name'];

                    $tree = $app->getSiteData()->getSiteMenu($menu_name, $website_id, $locale, null, false);
                    return $tree;
                }
            ];
        }
    }
}