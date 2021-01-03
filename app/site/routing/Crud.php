<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Routing;

use App\Base\Abstracts\Controllers\BaseRestPage;
use App\Base\Abstracts\Routing\BaseRouter;
use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use \HaydenPierce\ClassFinder\ClassFinder;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * Crud Router Class
 */
class Crud extends BaseRouter
{
    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    protected function getHttpVerbs() : array
    {
        return ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT'];
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

                $controllerClasses = ClassFinder::getClassesInNamespace('App\Site\Crud', ClassFinder::RECURSIVE_MODE);
                foreach ($controllerClasses as $controllerClass) {
                    if (is_subclass_of($controllerClass, BaseRestPage::class)) {
                        $group = "/crud";
                        $path = str_replace("app/site/crud/", "", str_replace("\\", "/", strtolower($controllerClass)));
                        $route_name = 'crud.'.str_replace("/", ".", trim($path, "/"));

                        $classMethod = self::CLASS_METHOD;
                        $verbs = $this->getClassHttpVerbs($controllerClass);

                        if (method_exists($controllerClass, 'getRoutePath')) {
                            $path = $this->getContainer()->call([$controllerClass, 'getRoutePath']) ?? $path;
                        }

                        if (is_string($path)) {
                            $path = explode(",", $path);
                        }

                        array_walk($path, function ($path_value, $key) use ($route_name, $group, $controllerClass, $classMethod, $verbs) {
                            $path_prefix = "";
                            if (method_exists($controllerClass, 'getRouteGroup')) {
                                $path_prefix = rtrim($this->getContainer()->call([$controllerClass, 'getRouteGroup']), '/').'/' ?? "";
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
}
