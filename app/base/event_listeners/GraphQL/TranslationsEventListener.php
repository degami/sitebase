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
use GraphQL\Type\Definition\ResolveInfo;

class TranslationsEventListener implements EventListenerInterface
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

        if (!isset($this->typesByName['TranslationEntry'])) {
            $typesByName['TranslationEntry'] = new ObjectType([
                'name' => 'TranslationEntry',
                'fields' => [
                    'key'   => ['type' => Type::nonNull(Type::string())],
                    'value' => ['type' => Type::nonNull(Type::string())],
                ]
            ]);
        }

        if (!isset($queryFields['translations'])) {
            //  translations: [TranslationEntry],
            $queryFields['translations'] = [
                'type' => Type::listOf($typesByName['TranslationEntry']),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                    $translationsPath = App::getDir(App::TRANSLATIONS);
                    $translationsArr = include($translationsPath.DS.'en.php');
                    $keys = array_keys($translationsArr);

                    return array_map(fn ($key) => ['key' => $key, 'value' => __($key)], $keys);
                }
            ];
        }
    }
}