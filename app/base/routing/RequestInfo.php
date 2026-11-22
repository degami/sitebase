<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Routing;

use App\App;
use App\Base\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Models\Website;
use FastRoute\Dispatcher;

/**
 * Request Information Class
 */
class RequestInfo 
{
    public function __construct(protected ?RouteInfo $route_info, protected ?Request $request) {
    }

    public function getRouteInfo() : ?RouteInfo
    {
        return $this->route_info;
    }

    public function getRequest() : ?Request
    {
        return $this->request;
    }

    public static function createFromRequest(Request $request) : RequestInfo
    {
        /** @var App $app */
        $app = App::getInstance();

        $routeInfo = null;
        if ($request != null && $app != null) {
            $website = null;
            if ($app->getEnvironment()->isCliServer()) {
                $website = $app->containerCall([Website::class, 'load'], ['id' => getenv('website_id')]);
            }

            foreach ($app->getClassFinder()->findRouterClasses() as $routerClass) {
                if (!call_user_func([$routerClass, 'isEnabled'])) {
                    continue;
                }

                $routeInfo = $app->containerCall(
                    [$app->getRouter($routerClass), 'getRouteInfo'],
                    [
                        'http_method' => $request->getMethod() ?? $_SERVER['REQUEST_METHOD'],
                        'request_uri' => $request->getRequestUri() ?? $_SERVER['REQUEST_URI'],
                        'domain' => ($app->getEnvironment()->isCliServer()) ? $website->domain : $request->getHost()
                    ]
                );

                if ($routeInfo?->getStatus() != Dispatcher::NOT_FOUND) {
                    break;
                }
            }
        }

        return new self($routeInfo, $request);
    }
}