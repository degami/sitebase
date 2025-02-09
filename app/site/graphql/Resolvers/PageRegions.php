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

namespace App\Site\GraphQL\Resolvers;

use App\App;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\Base\Interfaces\GraphQl\ResolverInterface;
use App\Site\Models\Rewrite;
use App\Base\Routing\RouteInfo;

class PageRegions implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        $app = App::getInstance();

        $locale = $args['locale'];

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

        // inject container into vars
        //$vars['container'] = $this->getContainer();

        // inject request object into vars
        //$vars['request'] = $this->getRequest();

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