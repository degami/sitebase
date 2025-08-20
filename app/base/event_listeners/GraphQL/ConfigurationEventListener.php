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

class ConfigurationEventListener implements EventListenerInterface
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

        if (!isset($typesByName['ConfigEntry'])) {
            $typesByName['ConfigEntry'] = new ObjectType([
                'name' => 'ConfigEntry',
                'fields' => [
                    'path'  => ['type' => Type::nonNull(Type::string())],
                    'value' => ['type' => Type::string()],
                ]
            ]);
        }

        if (!isset($typesByName['ConfigsResult'])) {
            $typesByName['ConfigsResult'] = new ObjectType([
                'name' => 'ConfigsResult',
                'fields' => [
                    'website' => ['type' => Type::nonNull($typesByName['Website'])],
                    'locale'  => ['type' => Type::string()],
                    'configs' => ['type' => Type::listOf($typesByName['ConfigEntry'])],
                ]
            ]);
        }

        if (!isset($queryFields['translations'])) {
            //  configuration: [ConfigsResult]
            $queryFields['configuration'] = [
                'type' => Type::listOf($typesByName['ConfigsResult']),
            ];
        }
    }
}