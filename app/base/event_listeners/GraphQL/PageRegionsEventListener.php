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
use App\Base\Models\Rewrite;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\Base\Routing\RouteInfo;
use GraphQL\Type\Definition\ResolveInfo;

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
        $app = \App\App::getInstance();
        $object = $e->getData('object');
        $queryFields = &$object->queryFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;
        $entrypoint = $object->entrypoint;

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

        if (!isset($queryFields['pageRegions'])) {
            //  pageRegions(rewrite_id: Int, route_path: String): PageRegionsResponse
            $queryFields['pageRegions'] = [
                'type' => $typesByName['PageRegionsResponse'],
                'args' => [
                    'rewrite_id' => ['type' => Type::int()],
                    'route_path' => ['type' => Type::string()],
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                    $locale = $args['locale'] ?? $app->getCurrentLocale();

                    $currentPage = null;
                    if (isset($args['rewrite_id'])) {
                        $rewrite = Rewrite::load($args['rewrite_id']);
                        $locale = $rewrite->getLocale();

                        $currentPage = static::getControllerByRewrite($rewrite, $app);
                    } else if (isset($args['route_path'])) {
                        $rewrite = Rewrite::getCollection()->where(['route' => $args['route_path'], 'locale' => $locale])->getFirst();
                        if ($rewrite) {
                            $currentPage = static::getControllerByRewrite($rewrite, $app);
                        } else {
                            $webRouter = $app->getWebRouter();
                            $routeInfo = $webRouter->getRequestInfo('GET', $args['route_path']);
                            if ($routeInfo) {
                                $currentPage = static::getControllerByRouteInfo($routeInfo, $app);
                            }
                        }
                    }

                    $regionsHtml = [];
                    foreach($app->getSiteData()->getPageRegions() as $region) {
                        foreach(['pre', 'post'] as $prefix) {
                            $pageRegion = $prefix.'_'.$region;
                            $regionsHtml[$pageRegion] = $app->getHtmlRenderer()->renderBlocks($pageRegion, $locale, $currentPage);
                        }
                    }
                    foreach(['after_body_open', 'before_body_close'] as $pageRegion) {
                        $regionsHtml[$pageRegion] = $app->getHtmlRenderer()->renderBlocks($pageRegion, $locale, $currentPage);
                    }

                    return ['locale' => $locale, 'regions' => [
                        'after_body_open' => $regionsHtml['after_body_open'] ?? null,
                        'before_body_close' => $regionsHtml['before_body_close'] ?? null,
                        'pre_menu' => $regionsHtml['pre_menu'] ?? null,
                        'post_menu' => $regionsHtml['post_menu'] ?? null,
                        'pre_header' => $regionsHtml['pre_header'] ?? null,
                        'post_header' => $regionsHtml['post_header'] ?? null,
                        'pre_content' => $regionsHtml['pre_content'] ?? null,
                        'post_content' => $regionsHtml['post_content'] ?? null,
                        'pre_footer' => $regionsHtml['pre_footer'] ?? null,
                        'post_footer' => $regionsHtml['post_footer'] ?? null,
                    ]];
                }
            ];            
        }
    }

    protected static function getControllerByRewrite(Rewrite $rewrite, App $app)
    {
        /** @var RouteInfo $routeInfo */
        $routeInfo = $rewrite->getRouteInfo();
        return static::getControllerByRouteInfo($routeInfo, $app);
    }

    protected static function getControllerByRouteInfo(RouteInfo $routeInfo, App $app)
    {
        $handler = $routeInfo->getHandler();

        $handlerType = reset($handler); $handlerMethod = end($handler);
        $currentPage = $app->containerMake($handlerType);

        $vars = $routeInfo->getVars();

        // inject routeInfo
        $vars['route_info'] = $routeInfo;

        // add route collected data
        $vars['route_data'] = $routeInfo->getVars();
        $vars['route_data']['_noLog'] = true;

        $currentPage->setRouteInfo($routeInfo);

        if ($currentPage instanceof FrontendPageWithObject) {
            $app->containerCall([$currentPage, $handlerMethod], $vars);
        }

        return $currentPage;
    }
}