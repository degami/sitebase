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

namespace App\Base\Routers;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Controllers\Admin\Index as AdminIndexPage;

/**
 * Admin Router Class
 */
class Admin extends Web
{
    public const ROUTER_TYPE = 'admin';

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

                $controllerClasses = array_unique(array_merge(
                    ClassFinder::getClassesInNamespace(App::BASE_CONTROLLERS_NAMESPACE . '\Admin', ClassFinder::RECURSIVE_MODE),
                    ClassFinder::getClassesInNamespace(App::CONTROLLERS_NAMESPACE . '\Admin', ClassFinder::RECURSIVE_MODE),
                ));
                foreach ($controllerClasses as $controllerClass) {
                    if (is_subclass_of($controllerClass, BasePage::class)) {

                        if (!$this->containerCall([$controllerClass, 'isEnabled'])) {
                            continue;
                        }

                        $group = "";
                        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", 
                            str_replace("app/base/controllers/", "", str_replace("\\", "/", strtolower($controllerClass)))
                        ));
                        $route_name = str_replace("/", ".", trim($path, "/"));

                        if (is_callable([$controllerClass, 'getPageRouteName'])) {
                            $route_name = $this->containerCall([$controllerClass, 'getPageRouteName']);
                        }

                        $classMethod = self::CLASS_METHOD;
                        $verbs = $this->getClassHttpVerbs($controllerClass);

                        if (($tmp = explode("/", $path, 2)) && count($tmp) > 1) {
                            $tmp = array_map(
                                function ($el) {
                                    return "/" . $el;
                                },
                                $tmp
                            );
                            if (!isset($this->routes[$tmp[0]])) {
                                $this->routes[$tmp[0]] = [];
                            }

                            $group = $tmp[0];
                            $path = $tmp[1];
                        }

                        if (method_exists($controllerClass, 'getRouteGroup')) {
                            $group = $this->containerCall([$controllerClass, 'getRouteGroup']) ?? $group;
                        }

                        if (method_exists($controllerClass, 'getRoutePath')) {
                            $path = $this->containerCall([$controllerClass, 'getRoutePath']) ?? $path;
                        }

                        if (is_string($path)) {
                            $path = explode(",", $path);
                        }

                        array_walk($path, function ($path_value, $key) use ($route_name, $group, $controllerClass, $classMethod, $verbs) {
                            if (!is_string($key)) {
                                $key = $route_name;
                            }

                            if (!$this->checkRouteParameters($path_value)) {
                                throw new InvalidValueException("'{$path_value}': Invalid route string", 1);
                            }

                            $this->addRoute($group, strtolower($key), "/" . ltrim($path_value, "/ "), $controllerClass, $classMethod, $verbs);
                        });
                    }
                }
                $this->addRoute($this->containerCall([AdminIndexPage::class, 'getRouteGroup']), 'admin.root', "/", AdminIndexPage::class, self::CLASS_METHOD);

                // cache controllers for faster access
                $this->setCachedControllers($this->routes);
            }
        }
        return $this->routes;
    }
}
