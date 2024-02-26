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
use App\Site\Controllers\Frontend\Page;
use App\Site\Controllers\Frontend\Search;
use App\Site\Controllers\Admin\TwoFa as Admin2Fa;
use App\Site\Controllers\Frontend\Users\TwoFa as Users2Fa;

/**
 * Web Router Class
 */
class Web extends BaseRouter
{
    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    protected function getHttpVerbs(): array
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

                $controllerClasses = ClassFinder::getClassesInNamespace('App\Site\Controllers', ClassFinder::RECURSIVE_MODE);
                foreach ($controllerClasses as $controllerClass) {
                    if (is_subclass_of($controllerClass, BasePage::class)) {
                        if ($controllerClass == Search::class && !$this->getEnv('ELASTICSEARCH')) {
                            continue;
                        }

                        if ($controllerClass == Users2Fa::class && !$this->getEnv('USE2FA_USERS')) {
                            continue;
                        }

                        if ($controllerClass == Admin2Fa::class && !$this->getEnv('USE2FA_ADMIN')) {
                            continue;
                        }

                        $group = "";
                        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", strtolower($controllerClass)));
                        $route_name = str_replace("/", ".", trim($path, "/"));

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
                            $group = $this->getContainer()->call([$controllerClass, 'getRouteGroup']) ?? $group;
                        }

                        if (method_exists($controllerClass, 'getRoutePath')) {
                            $path = $this->getContainer()->call([$controllerClass, 'getRoutePath']) ?? $path;
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

            $rewrite = $this->getContainer()->call([Rewrite::class, 'loadByCondition'], ['condition' => ['url' => $uri, 'website_id' => $website_id]]);
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
