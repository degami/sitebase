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

namespace App\Base\Abstracts\Routing;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Exceptions\InvalidValueException;
use App\Site\Models\Rewrite;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use Exception;

use function FastRoute\simpleDispatcher;

/**
 * Base Router Class
 */
abstract class BaseRouter extends ContainerAwareObject
{
    public const REGEXP_ROUTE_VARIABLE_EXPRESSION = "(:([^{}]*|\{([^{}]*|\{[^{}]*\})*\})*)?";
    public const CLASS_METHOD = 'renderPage';

    /**
     * @var Dispatcher dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @var array routes
     */
    protected array $routes;

    /**
     * @var array reserved parameter names
     */
    protected array $avoid_parameter_names = ['container', 'route_info', 'route_data'];

    /**
     * class constructor
     *
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function __construct(
        protected ContainerInterface $container
    ) {
        parent::__construct($container);

        $router_name = $this->getRouterName();

        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->startMeasure($router_name . '_construct', ucfirst($router_name) . ' construct');
        }

        $this->dispatcher = simpleDispatcher(
            function (RouteCollector $r) {
                foreach ($this->getRoutes() as $group => $paths) {
                    if ($group != "") {
                        $r->addGroup(
                            $group,
                            function (RouteCollector $r) use ($paths) {
                                $this->insertRoutes($r, $paths);
                            }
                        );
                    } else {
                        $this->insertRoutes($r, $paths);
                    }
                }
            }
        );

        if ($this->getContainer()->has('app')) {
            // dispatch "dispatcher_ready" event
            $this->getApp()->event('dispatcher_ready', ['router' => $this, 'dispatcher' => $this->dispatcher]);
        }

        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->stopMeasure($router_name . '_construct');
        }
    }

    /**
     * gets Router name
     *
     * @return string
     */
    protected function getRouterName(): string
    {
        $router_name = explode("\\", strtolower(get_class($this)));
        $router_name = array_pop($router_name);

        return $router_name;
    }

    /**
     * checks route parameters
     *
     * @param string $route
     * @return bool
     */
    protected function checkRouteParameters(string $route): bool
    {
        if (preg_match("/\{(" . implode("|", $this->avoid_parameter_names) . ")(:.*?)?\}/i", $route)) {
            return false;
        }
        return true;
    }

    /**
     * checks route http verbs
     *
     * @param array $verbs
     * @return array
     */
    protected function checkRouteVerbs(array $verbs): array
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
     * @param RouteCollector $r
     * @param array $paths
     * @return self
     */
    private function insertRoutes(RouteCollector $r, array $paths): BaseRouter
    {
        foreach ($paths as $p) {
            $this->insertRoute($r, $p);
        }

        return $this;
    }

    /**
     * adds a route
     *
     * @param RouteCollector $r
     * @param array $p
     * @return self
     */
    private function insertRoute(RouteCollector $r, array $p): BaseRouter
    {
        $r->addRoute($p['verbs'], $p['path'], [$p['class'], $p['method']]);

        return $this;
    }

    /**
     * gets route by class
     *
     * @param string $class
     * @param string|null $uri
     * @param string|null $http_method
     * @return array
     */
    protected function getRouteByClass(string $class, $uri = null, $http_method = null): array
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

        if (count($out) > 1) {
            // try to preg_match elements found with $uri, to find the most suitable
            $regexp = "/\{.*?" . self::REGEXP_ROUTE_VARIABLE_EXPRESSION . "\}/i";

            foreach ($out as $elem) {
                if ($http_method != null && !in_array($http_method, (array)$elem['verbs'])) {
                    // http method is not valid. skip check
                    continue;
                }
                $path_regexp = $elem['path'];
                if (preg_match_all($regexp, $elem['path'], $matches)) {
                    foreach ($matches[0] as $k => $placeholder) {
                        if ($matches[1][$k] != '') {
                            $path_regexp = str_replace($placeholder, '(' . substr($matches[1][$k], 1) . ')', $path_regexp);
                        } else {
                            $path_regexp = str_replace($placeholder, '(.*?)', $path_regexp);
                        }
                    }
                }
                if (preg_match('/^' . str_replace('/', '\/', $path_regexp) . '$/i', $uri)) {
                    return $elem;
                }
            }
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
     * @param string[] $verbs
     * @return self
     */
    public function addRoute(string $group, string $name, string $path, string $class, string $method = 'renderPage', array $verbs = ['GET', 'POST']): BaseRouter
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
     * @param string $route
     * @return bool
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function checkRoute(string $route): bool
    {
        try {
            return !in_array($this->getRequestInfo('GET', $route)->getStatus(), [Dispatcher::NOT_FOUND, Dispatcher::METHOD_NOT_ALLOWED]);
        } catch (DependencyException | NotFoundException $e) {
        }

        return false;
    }

    /**
     * gets dispatcher
     *
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    /**
     * gets a single route
     *
     * @param string $name
     * @return array|null
     */
    public function getRoute(string $name): ?array
    {
        foreach ($this->getRoutes() as $group => $paths) {
            foreach ($paths as $p) {
                if ($p['name'] == $name) {
                    $p['path'] = $group . $p['path'];
                    return $p;
                }
            }
        }
        return null;
    }

    /**
     * return base site url
     *
     * @return string|null
     * @throws BasicException
     */
    public function getBaseUrl(): ?string
    {
        if ($this->getEnv('BASE_URL') != null) {
            return $this->getEnv('BASE_URL');
        }

        $parsed = parse_url(
            sprintf(
                "%s://%s%s%s",
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http',
                (is_numeric($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ':' . $_SERVER['SERVER_PORT'] : '',
                $this->getSiteData()->currentServerName(),
                $_SERVER['REQUEST_URI']
            )
        );

        if ($parsed) {
            return $parsed['scheme'] . '://' . $parsed['host'];
        }
        return null;
    }

    /**
     * Returns url for given route
     *
     * @param string $route_name
     * @param array $route_params
     * @return string
     * @throws BasicException
     */
    public function getUrl(string $route_name, $route_params = []): string
    {
        $dispatcherInfo = $this->getRoute($route_name);
        if ($dispatcherInfo != null) {
            foreach ($route_params as $var_name => $value) {
                $regexp = "/\{" . $var_name . self::REGEXP_ROUTE_VARIABLE_EXPRESSION . "\}/i";
                $dispatcherInfo['path'] = preg_replace($regexp, $value, $dispatcherInfo['path']);
            }
            $dispatcherInfo['path'] = str_replace(['[',']'],"", $dispatcherInfo['path']);
            return $this->getBaseUrl() . $dispatcherInfo['path'];
        }

        return $this->getBaseUrl();
    }

    /**
     * compose cache key
     *
     * @param string $element
     * @return string
     */
    protected function getCacheKey(string $element): string
    {
        return strtolower($this->getRouterName() . '.' . $element);
    }

    /**
     * gets array from cache
     *
     * @param $array_name
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function getArrayFromCache($array_name): array
    {
        $out = [];
        $cache_key = $this->getCacheKey($array_name);
        if ($this->getCache()->has($cache_key)) {
            $out += $this->getCache()->get($cache_key);
        }

        return $out;
    }

    /**
     * sets array in cache
     *
     * @param $array_name
     * @param $array
     * @return self
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function setArrayInCache($array_name, $array): self
    {
        $cache_key = $this->getCacheKey($array_name);
        $this->getCache()->set($cache_key, $array);

        return $this;
    }

    /**
     * sets cached routes
     *
     * @param array $routes
     * @return $this
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function setCachedRoutes(array $routes): BaseRouter
    {
        return $this->setArrayInCache('routes', $routes);
    }

    /**
     * gets cached routes
     *
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function getCachedRewrites(): array
    {
        return $this->getArrayFromCache('routes');
    }

    /**
     * sets cached controllers
     *
     * @param array $controllers
     * @return self
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function setCachedControllers(array $controllers): BaseRouter
    {
        return $this->setArrayInCache('controllers', $controllers);
    }

    /**
     * gets cached controllers
     *
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function getCachedControllers(): array
    {
        return $this->getArrayFromCache('controllers');
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
    public function getRequestInfo(?string $http_method = null, ?string $request_uri = null, ?string $domain = null): RouteInfo
    {
        if (empty($http_method)) {
            $http_method = $_SERVER['REQUEST_METHOD'];
        }
        if (empty($request_uri)) {
            $request_uri = $_SERVER['REQUEST_URI'];
        }
        if (empty($domain)) {
            $domain = $this->getSiteData()->currentServerName();
        }

        // Fetch method and URI from somewhere
        $parsed = parse_url($request_uri);

        // Strip query string (?foo=bar) and decode URI
        $uri = rawurldecode($parsed['path']);
        $route = $uri;
        $route_name = null;
        $rewrite_id = null;

        $dispatcher_info = $this->getDispatcher()->dispatch($http_method, $uri);
        if ($dispatcher_info[0] == Dispatcher::NOT_FOUND) {
            $cached_rewrites = $this->getCachedRewrites();

            /** @var Rewrite $rewrite */
            $rewrite = null;
            if (isset($cached_rewrites[$domain][$uri])) {
                $rewrite = $this->containerCall([Rewrite::class, 'new'], ['initial_data' => $cached_rewrites[$domain][$uri]]);
            } else {
                // if not found, check the rewrites table if applicable
                $rewrite = $this->checkRewrites($uri);
            }

            if (!is_null($rewrite) && ($rewrite instanceof Rewrite)) {
                $dispatcher_info = $this->getDispatcher()->dispatch($http_method, $rewrite->getRoute());
                $rewrite_id = $rewrite->getId();
            }
        }

        if ($dispatcher_info[0] == Dispatcher::NOT_FOUND) {
            // Add index controller if needed
            if (preg_match("#/$#", $uri)) {
                $uri .= 'index';
            }
            $route = $uri;
            $dispatcher_info = $this->getDispatcher()->dispatch($http_method, $uri);
        }

        if ($dispatcher_info[0] == Dispatcher::FOUND) {
            $route_info = $this->getRouteByClass($dispatcher_info[1][0], $uri, $http_method);
            if (isset($route_info['name'])) {
                $route_name = $route_info['name'];
            }
        }

        // return a RouteInfo instance
        return $this->containerMake(RouteInfo::class, [
            'dispatcher_info' => $dispatcher_info,
            'http_method' => $http_method,
            'uri' => $uri,
            'route' => $route,
            'route_name' => $route_name,
            'rewrite' => $rewrite_id,
        ]);
    }

    /**
     * gets rewrite object by uri
     *
     * @param string $uri
     * @return Rewrite|null
     */
    protected function checkRewrites(string $uri): ?Rewrite
    {
        // not every router should check for rewrites. return null by default

        return null;
    }

    /**
     * gets Class applicable route http verbs
     *
     * @param $controller_class
     * @return array
     * @throws InvalidValueException
     */
    protected function getClassHttpVerbs($controller_class): array
    {
        if (method_exists($controller_class, 'getRouteVerbs')) {
            $verbs = $this->containerCall([$controller_class, 'getRouteVerbs']) ?? $this->getHttpVerbs();
            if (!is_array($verbs)) {
                $verbs = [$verbs];
            }
            if (!empty($errors = $this->checkRouteVerbs($verbs))) {
                throw new InvalidValueException(implode(',', $errors) . ": Invalid route verbs", 1);
            }

            return $verbs;
        }

        return $this->getHttpVerbs();
    }

    /**
     * defines http default verbs
     *
     * @return array
     */
    abstract protected function getHttpVerbs(): array;
}
