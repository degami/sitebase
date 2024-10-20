<?php

namespace App\Site\GraphQL\Resolvers;

use App\App;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\Base\GraphQl\ResolverInterface;
use App\Site\Models\Rewrite;
use App\Site\Routing\RouteInfo;

class PageRegions implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        $app = App::getInstance();

        $locale = $args['locale'];

        $currentPage = null;
        if (isset($args['rewrite_id'])) {
            $rewrite = Rewrite::load($args['rewrite_id']);

            /** @var RouteInfo $routeInfo */
            $routeInfo = $rewrite->getRouteInfo();
            $locale = $rewrite->getLocale();
            $handler = $rewrite->getRouteInfo()->getHandler();

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

            $currentPage->setRouteInfo($routeInfo);

            if ($currentPage instanceof FrontendPageWithObject) {
                $app->containerCall([$currentPage, $handlerMethod], $vars);
            }
        }

        $regionsHtml = [];
        foreach($app->getSiteData()->getPageRegions() as $region) {
            foreach(['pre', 'post'] as $prefix) {
                $pageRegion = $prefix.'_'.$region;
                $regionsHtml[$pageRegion] = $app->getHtmlRenderer()->renderBlocks($pageRegion, $locale, $currentPage);
            }
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
}