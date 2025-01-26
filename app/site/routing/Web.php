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

namespace App\Site\Routing;

use App\App;
use App\Base\Abstracts\Routing\BaseRouter;
use App\Site\Models\Rewrite;
use App\Site\Models\Website;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Interfaces\Router\WebRouterInterface;
use App\Site\Controllers\Frontend\Page;
use App\Base\Routing\RouteInfo;

/**
 * Web Router Class
 */
class Web extends BaseRouter implements WebRouterInterface
{
    public const ROUTER_TYPE = 'web';

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getHttpVerbs(): array
    {
        return ['GET', 'POST'];
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

                $controllerClasses = ClassFinder::getClassesInNamespace(App::CONTROLLERS_NAMESPACE, ClassFinder::RECURSIVE_MODE);
                foreach ($controllerClasses as $controllerClass) {
                    if (is_subclass_of($controllerClass, BasePage::class)) {

                        if (!$this->containerCall([$controllerClass, 'isEnabled'])) {
                            continue;
                        }

                        $group = "";
                        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", strtolower($controllerClass)));
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
                $this->addRoute('', 'frontend.root.withlang', "/{lang:[a-z]{2}}[/]", Page::class, 'showFrontPage');
                $this->addRoute('', 'frontend.root', "/", Page::class, 'showFrontPage');

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

    /**
     * gets Rewrite Object by uri
     *
     * @param string $uri
     * @return Rewrite|null
     */
    protected function checkRewrites(string $uri): ?Rewrite
    {
        try {
            $cached_routes = $this->getCachedRewrites();

            /** @var Website $website */
            $website = $this->getSiteData()->getCurrentWebsite();
            $domain = $website->getDomain();
            $website_id = $website->getId();

            $rewrite = $this->containerCall([Rewrite::class, 'loadByCondition'], ['condition' => ['url' => $uri, 'website_id' => $website_id]]);
            if ($rewrite instanceof Rewrite) {
                $cached_routes[$domain][$uri] = $rewrite->getData();
                $this->setCachedRoutes($cached_routes);

                return $rewrite;
            }
        } catch (Exception $e) {
        }

        return parent::checkRewrites($uri);
    }

    /**
     * gets rewrite's object url
     *
     * @param Rewrite $rewrite
     * @return string
     * @throws BasicException
     */
    public function getRewriteUrl(Rewrite $rewrite): string
    {
        return $this->getBaseUrl() . $rewrite->getUrl();
    }
}
