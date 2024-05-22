<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Routing;

use App\Base\Abstracts\Controllers\BaseWebhookPage;
use App\Base\Abstracts\Routing\BaseRouter;
use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Site\Routing\RouteInfo;

/**
 * Webhooks Router Class
 */
class Webhooks extends BaseRouter
{
    public const ROUTER_TYPE = 'webhook';

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    protected function getHttpVerbs(): array
    {
        return ['POST'];
    }

    /**
     * gets routes
     *
     * @return array
     * @throws BasicException
     * @throws InvalidValueException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Exception
     */
    public function getRoutes(): array
    {
        if (empty($this->routes)) {
            $this->routes = $this->getCachedControllers();
            if (empty($this->routes)) {
                // collect routes

                $controllerClasses = ClassFinder::getClassesInNamespace('App\Site\Webhooks', ClassFinder::RECURSIVE_MODE);
                foreach ($controllerClasses as $controllerClass) {
                    if (is_subclass_of($controllerClass, BaseWebhookPage::class)) {
                        $group = "/webhooks";
                        $path = str_replace("app/site/webhooks/", "", str_replace("\\", "/", strtolower($controllerClass)));
                        $route_name = 'webhooks.' . str_replace("/", ".", trim($path, "/"));

                        if (is_callable([$controllerClass, 'getPageRouteName'])) {
                            $route_name = $this->containerCall([$controllerClass, 'getPageRouteName']);
                        }

                        $classMethod = self::CLASS_METHOD;
                        $verbs = $this->getClassHttpVerbs($controllerClass);

                        if (method_exists($controllerClass, 'getRoutePath')) {
                            $path = $this->containerCall([$controllerClass, 'getRoutePath']) ?? $path;
                        }

                        if (is_string($path)) {
                            $path = explode(",", $path);
                        }

                        array_walk($path, function ($path_value, $key) use ($route_name, $group, $controllerClass, $classMethod, $verbs) {
                            $path_prefix = "";
                            if (method_exists($controllerClass, 'getRouteGroup')) {
                                $path_prefix = rtrim($this->containerCall([$controllerClass, 'getRouteGroup']), '/') . '/' ?? "";
                            }

                            if (!is_string($key)) {
                                $key = $route_name;
                            }

                            if (!$this->checkRouteParameters($path_value)) {
                                throw new InvalidValueException("'{$path_value}': Invalid route string", 1);
                            }

                            $this->addRoute($group, strtolower($key), "/" . ltrim($path_prefix, '/') . ltrim($path_value, "/ "), $controllerClass, $classMethod, $verbs);
                        });
                    }
                }

                // cache controllers for faster access
                $this->setCachedControllers($this->routes);
            }
        }
        return $this->routes;
    }

    /**
     * returns a RouteInfo instance for current request
     *
     * @param string|null $http_method
     * @param string|null $request_uri
     * @param string|null $domain
     * @return RouteInfo
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getRequestInfo($http_method = null, $request_uri = null, $domain = null): RouteInfo
    {
        // set request info type as crud
        return parent::getRequestInfo($http_method, $request_uri, $domain)->setType(self::ROUTER_TYPE);
    }
}
