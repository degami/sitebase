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
use App\Base\Models\Website;
use GraphQL\Type\Definition\ResolveInfo;

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
        $app = App::getInstance();
        $object = $e->getData('object');
        $queryFields = &$object->queryFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;
        $entrypoint = $object->entrypoint;

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

        if (!isset($queryFields['configuration'])) {
            //  configuration: [ConfigsResult]
            $queryFields['configuration'] = [
                'type' => Type::listOf($typesByName['ConfigsResult']),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                    $websites = Website::getCollection()->getItems();

                    $configPaths = [
                        'app/frontend/homepage',
                        'app/frontend/homepage_redirects_to_language',
                        'app/frontend/langs',
                        'app/frontend/main_menu',
                        'app/frontend/assets_domain',
                        'app/frontend/menu_with_logo',
                    ];

                    $out = [];

                    foreach ($websites as $website) {
                        $website_id = $website->getId();

                        foreach([null] + $app->getSiteData()->getSiteLocales($website_id) as $locale) {
                            $configs = [];
                            foreach ($configPaths as $path) {
                                $value = $app->getSiteData()->getConfigValue($path, $website_id, $locale);
                                if ($value == null && $path == 'app/frontend/homepage') {
                                    $configs[] = ['path' => $path, 'value' => $app->getSiteData()->getConfigValue($path, $website_id, 'en')];
                                } else {
                                    $configs[] = ['path' => $path, 'value' => $value];
                                }
                            }

                            
                            $configs[] = ['path' => 'app/mapbox/api_key', 'value' => $app->getEnvironment()->getVariable('MAPBOX_API_KEY')];
                            $configs[] = ['path' => 'app/googlemaps/api_key', 'value' => $app->getEnvironment()->getVariable('GOOGLE_API_KEY')];

                            $out[] = [
                                'website' => $website,
                                'locale' => $locale,
                                'configs' => $configs,
                            ];
                        }
                    }

                    return $out;
                }
            ];
        }
    }
}