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

use \FastRoute\Dispatcher;
use \FastRoute\RouteCollector;
use \HaydenPierce\ClassFinder\ClassFinder;
use \Psr\Container\ContainerInterface;
use \App\Base\Exceptions\InvalidValueException;
use \App\Base\Abstracts\Controllers\BasePage;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Site\Controllers\Frontend\Page;
use function FastRoute\simpleDispatcher;

/**
 * Web Router Class
 */
class Web extends ContainerAwareObject
{
    const CLASS_METHOD = 'renderPage';
    const HTTP_VERBS = ['GET', 'POST'];

    /**
     * @var Dispatcher dispatcher
     */
    protected $dispatcher;

    /**
     * @var array routes
     */
    protected $routes;

    /**
     * @var array reserved parameter names
     */
    protected $avoid_parameter_names = ['container', 'route_info', 'route_data'];

    /**
     * class constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->routes = [];
        $controllerClasses = ClassFinder::getClassesInNamespace('App\Site\Controllers', ClassFinder::RECURSIVE_MODE);
        foreach ($controllerClasses as $controllerClass) {
            if (is_subclass_of($controllerClass, BasePage::class)) {
                $group = "";
                $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", strtolower($controllerClass)));
                $route_name = str_replace("/", ".", trim($path, "/"));

                $classMethod = self::CLASS_METHOD;
                $verbs = self::HTTP_VERBS;

                if (($tmp = explode("/", $path, 2)) && count($tmp) > 1) {
                    $tmp = array_map(
                        function ($el) {
                            return "/".$el;
                        },
                        $tmp
                    );
                    if (!isset($this->routes[$tmp[0]])) {
                        $this->routes[$tmp[0]] = [];
                    }

                    $group = $tmp[0];
                    $path = $tmp[1];
                } else {
                    // everithing should be fine
                }

                if (method_exists($controllerClass, 'getRouteGroup')) {
                    $group = $this->getContainer()->call([$controllerClass, 'getRouteGroup']) ?? $group;
                }

                if (method_exists($controllerClass, 'getRouteVerbs')) {
                    $verbs = $this->getContainer()->call([$controllerClass, 'getRouteVerbs']) ?? $verbs;
                    if (!is_array($verbs)) {
                        $verbs = [$verbs];
                    }
                    if (!empty($errors = $this->checkRouteVerbs($verbs))) {
                        throw new InvalidValueException(implode(',', $errors).": Invalid route verbs", 1);
                    }
                }

                if (method_exists($controllerClass, 'getRoutePath')) {
                    $path = $this->getContainer()->call([$controllerClass, 'getRoutePath']) ?? $path;
                    if (!$this->checkRouteParameters($path)) {
                        throw new InvalidValueException("'{$path}': Invalid route string", 1);
                    }
                }

                // multiple paths can be specified, comma separated
                foreach (explode(",", $path) as $key => $path_value) {
                    $this->addRoute($group, $route_name, "/".ltrim($path_value, "/ "), $controllerClass, $classMethod, $verbs);
                }
            }
        }
        $this->addRoute('', 'frontend.root.withlang', "/{lang:[a-z]{2}}[/]", Page::class, 'showFrontPage');
        $this->addRoute('', 'frontend.root', "/", Page::class, 'showFrontPage');

        $this->dispatcher = simpleDispatcher(
            function (RouteCollector $r) {
                foreach ($this->routes as $group => $paths) {
                    if ($group != "") {
                        $r->addGroup(
                            $group,
                            function (RouteCollector $r) use ($paths) {
                                $this->_insertRoutes($r, $paths);
                            }
                        );
                    } else {
                        $this->_insertRoutes($r, $paths);
                    }
                }
            }
        );
    }

    /**
     * checks route parameters
     *
     * @param  string $route
     * @return boolean
     */
    protected function checkRouteParameters($route)
    {
        if (preg_match("/\{(".implode("|", $this->avoid_parameter_names).")(:.*?)?\}/i", $route)) {
            return false;
        }
        return true;
    }

    /**
     * checks route http verbs
     *
     * @param  array $verbs
     * @return array
     */
    protected function checkRouteVerbs($verbs)
    {
        if (!is_array($verbs)) {
            $verbs = [$verbs];
        }

        return array_diff(
            array_filter(array_map('strtoupper', array_map('trim', $verbs))),
            ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT']
        );
    }

    /**
     * adds routes
     *
     * @param  RouteCollector $r
     * @param  array          $paths
     * @return self
     */
    private function _insertRoutes(RouteCollector $r, array $paths)
    {
        foreach ($paths as $p) {
            $this->_insertRoute($r, $p);
        }

        return $this;
    }

    /**
     * adds a route
     *
     * @param  RouteCollector $r
     * @param  array          $p
     * @return self
     */
    private function _insertRoute(RouteCollector $r, array $p)
    {
        $r->addRoute($p['verbs'], $p['path'], [$p['class'], $p['method']]);

        return $this;
    }

    /**
     * gets route by class
     *
     * @param  string $class
     * @return array
     */
    protected function getRouteByClass($class)
    {
        $out = [];
        foreach (array_keys($this->routes) as $group) {
            $out = array_merge(
                $out,
                array_filter(
                    $this->routes[$group],
                    function ($el) use ($class) {
                        return $el['class'] == $class;
                    }
                )
            );
        }
        return reset($out);
    }

    /**
     * adds a route
     *
     * @param string $group
     * @param string $name
     * @param string $path
     * @param string $class
     * @param string $method
     */
    public function addRoute($group, $name, $path, $class, $method = 'renderPage', $verbs = ['GET', 'POST'])
    {
        $this->routes[$group][] = [
            'path' => $path,
            'class' => $class,
            'method' => $method,
            'name' => $name,
            'verbs' => $verbs
        ];
        return $this;
    }

    /**
     * checks a route
     *
     * @param  ContainerInterface $container
     * @param  string             $route
     * @return boolean
     */
    public function checkRoute(ContainerInterface $container, $route)
    {
        return !in_array($this->getRequestInfo($container, 'GET', $route)->getStatus(), [Dispatcher::NOT_FOUND, Dispatcher::METHOD_NOT_ALLOWED]);
    }

    /**
     * gets dispatcher
     *
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * gets routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * gets a single route
     *
     * @param  string $name
     * @return array
     */
    public function getRoute($name)
    {
        foreach ($this->getRoutes() as $group => $paths) {
            foreach ($paths as $p) {
                if ($p['name'] == $name) {
                    $p['path'] = $group . $p['path'];
                    return $p;
                }
            }
        }
    }

    /**
     * return base site url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if ($this->getEnv('BASE_URL') != null) {
            return $this->getEnv('BASE_URL');
        }

        $parsed = parse_url(
            sprintf(
                "%s://%s%s%s",
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http',
                (is_numeric($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ':'.$_SERVER['SERVER_PORT'] : '',
                $this->getSiteData()->currentServerName(),
                $_SERVER['REQUEST_URI']
            )
        );
        if ($parsed) {
            return $parsed['scheme'].'://'.$parsed['host'];
        }
        return null;
    }

    /**
     * Returns url for given route
     *
     * @param  string $route_name
     * @param  array  $route_params
     * @return string
     */
    public function getUrl($route_name, $route_params = [])
    {
        $dispatcherInfo = $this->getRoute($route_name);
        foreach ($route_params as $varname => $value) {
            $dispatcherInfo['path'] = preg_replace("/\{".$varname."(:.*?)?\}/i", $value, $dispatcherInfo['path']);
        }
        return $this->getBaseUrl() . $dispatcherInfo['path'];
    }

    /**
     * gets cached routes
     *
     * @param  ContainerInterface $container
     * @return array
     */
    protected function getCachedRoutes(ContainerInterface $container)
    {
        if ($container->get('cache')->has('web.routes')) {
            return $container->get('cache')->get('web.routes');
        }

        return [];
    }

    /**
     * returns a RouteInfo instance for current request
     *
     * @param  ContainerInterface $container
     * @param  string             $http_method
     * @param  string             $request_uri
     * @param  string             $domain
     * @return RouteInfo
     */
    public function getRequestInfo(ContainerInterface $container, $http_method = null, $request_uri = null, $domain = null)
    {
        if (empty($http_method)) {
            $http_method = $_SERVER['REQUEST_METHOD'];
        }
        if (empty($request_uri)) {
            $request_uri = $_SERVER['REQUEST_URI'];
        }
        if (empty($domain)) {
            $domain = $container->get('site_data')->currentServerName();
        }

        // Fetch method and URI from somewhere
        $httpMethod = $http_method;
        $parsed = parse_url($request_uri);

        // Strip query string (?foo=bar) and decode URI
        $uri = rawurldecode($parsed['path']);
        $route = $uri;
        $route_name = null;
        $rewrite_id = null;

        $dispatcherInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);
        if ($dispatcherInfo[0] == Dispatcher::NOT_FOUND) {
            $cached_routes = $this->getCachedRoutes($container);
            if (isset($cached_routes[$domain][$uri])) {
                $rewrite = (object)$cached_routes[$domain][$uri];
                $route = $rewrite->route;
                $dispatcherInfo = $this->getDispatcher()->dispatch($httpMethod, $rewrite->route);
                $rewrite_id = $rewrite->id;
            } else {
                // if not found, check the rewrites table
                $website_id = $container->get('site_data')->getCurrentWebsiteId();
                $rewrite = $container->get('db')->rewrite()->where(['url' => $uri, 'website_id' => $website_id])->fetch();
                if ($rewrite) {
                    $route = $rewrite->route;
                    $dispatcherInfo = $this->getDispatcher()->dispatch($httpMethod, $rewrite->route);
                    $rewrite_id = $rewrite->getId();

                    $cached_routes[$domain][$uri] = $rewrite->getData();
                    $container->get('cache')->set('web.routes', $cached_routes);
                }
            }
        }

        if ($dispatcherInfo[0] == Dispatcher::NOT_FOUND) {
            // Add index controller if needed
            if (preg_match("#/$#", $uri)) {
                $uri.='index';
            }
            $route = $uri;
            $dispatcherInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);
        }

        if ($dispatcherInfo[0] == Dispatcher::FOUND) {
            $route_info = $this->getRouteByClass($dispatcherInfo[1][0]);
            if (isset($route_info['name'])) {
                $route_name = $route_info['name'];
            }
        }

        // return a RouteInfo instance
        return $container->make(
            RouteInfo::class,
            [
            'dispatcher_info' => $dispatcherInfo,
            'http_method' => $httpMethod,
            'uri' => $uri,
            'route' => $route,
            'route_name' => $route_name,
            'rewrite' => $rewrite_id,
            ]
        );
    }
}
