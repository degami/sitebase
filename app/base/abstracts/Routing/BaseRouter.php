<?php


namespace App\Base\Abstracts\Routing;


use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Exceptions\InvalidValueException;
use App\Site\Routing\Crud;
use App\Site\Routing\RouteInfo;
use App\Site\Routing\Web;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Container\ContainerInterface;
use Exception;
use function FastRoute\simpleDispatcher;

abstract class BaseRouter extends ContainerAwareObject
{
    const REGEXP_ROUTEVAR_EXPRESSION = "(:([^{}]*|\{([^{}]*|\{[^{}]*\})*\})*)?";
    const CLASS_METHOD = 'renderPage';

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
     * @throws Exception
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $routername = $this->getRouterName();

        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->startMeasure($routername.'_construct', ucfirst($routername).' construct');
        }

        $this->dispatcher = simpleDispatcher(
            function (RouteCollector $r) {
                foreach ($this->getRoutes() as $group => $paths) {
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

        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->stopMeasure($routername.'_construct');
        }
    }

    /**
     * gets Router name
     *
     * @return string
     */
    protected function getRouterName(): string
    {
        $routername = explode("\\", strtolower(get_class($this)));
        $routername = array_pop($routername);

        return $routername;
    }

    /**
     * checks route parameters
     *
     * @param string $route
     * @return boolean
     */
    protected function checkRouteParameters($route): bool
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
    protected function checkRouteVerbs($verbs): array
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
    private function _insertRoutes(RouteCollector $r, array $paths): BaseRouter
    {
        foreach ($paths as $p) {
            $this->_insertRoute($r, $p);
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
    private function _insertRoute(RouteCollector $r, array $p): BaseRouter
    {
        $r->addRoute($p['verbs'], $p['path'], [$p['class'], $p['method']]);

        return $this;
    }

    /**
     * gets route by class
     *
     * @param string $class
     * @param string|null $uri
     * @param string|null $httpMethod
     * @return array
     */
    protected function getRouteByClass($class, $uri = null, $httpMethod = null): array
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
            $regexp = "/\{.*?" . Web::REGEXP_ROUTEVAR_EXPRESSION . "\}/i";

            foreach ($out as $elem) {
                if ($httpMethod != null && !in_array($httpMethod, (array)$elem['verbs'])) {
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
    public function addRoute($group, $name, $path, $class, $method = 'renderPage', $verbs = ['GET', 'POST']): BaseRouter
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
     * @param ContainerInterface $container
     * @param string $route
     * @return boolean
     */
    public function checkRoute(ContainerInterface $container, $route): bool
    {
        return !in_array($this->getRequestInfo($container, 'GET', $route)->getStatus(), [Dispatcher::NOT_FOUND, Dispatcher::METHOD_NOT_ALLOWED]);
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
     * @return array
     */
    public function getRoute($name): ?array
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
     * @return string
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
    public function getUrl($route_name, $route_params = []): string
    {
        $dispatcherInfo = $this->getRoute($route_name);
        if ($dispatcherInfo != null) {
            foreach ($route_params as $varname => $value) {
                // $regexp = "/\{".$varname."(:.*?)?\}/i"
                $regexp = "/\{" . $varname . self::REGEXP_ROUTEVAR_EXPRESSION . "\}/i";
                $dispatcherInfo['path'] = preg_replace($regexp, $value, $dispatcherInfo['path']);
            }
            return $this->getBaseUrl() . $dispatcherInfo['path'];
        }

        return $this->getBaseUrl();
    }

    /**
     * gets cached routes
     *
     * @param ContainerInterface $container
     * @return array
     */
    protected function getCachedRoutes(ContainerInterface $container): array
    {
        if ($container->get('cache')->has('web.routes')) {
            return $container->get('cache')->get('web.routes');
        }

        return [];
    }

    /**
     * returns a RouteInfo instance for current request
     *
     * @param ContainerInterface $container
     * @param string|null $http_method
     * @param string|null $request_uri
     * @param string|null $domain
     * @return RouteInfo
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRequestInfo(ContainerInterface $container, $http_method = null, $request_uri = null, $domain = null): RouteInfo
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
                $rewrite = $container->get('db')->table('rewrite')->where(['url' => $uri, 'website_id' => $website_id])->fetch();
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
                $uri .= 'index';
            }
            $route = $uri;
            $dispatcherInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);
        }

        if ($dispatcherInfo[0] == Dispatcher::FOUND) {
            $route_info = $this->getRouteByClass($dispatcherInfo[1][0], $uri, $httpMethod);
            if (isset($route_info['name'])) {
                $route_name = $route_info['name'];
            }
        }

        // return a RouteInfo instance
        return $container->make(RouteInfo::class, [
            'dispatcher_info' => $dispatcherInfo,
            'http_method' => $httpMethod,
            'uri' => $uri,
            'route' => $route,
            'route_name' => $route_name,
            'rewrite' => $rewrite_id,
        ]);
    }

    /**
     * gets Class applicable route http verbs
     *
     * @param $controllerClass
     * @return array
     * @throws InvalidValueException
     */
    protected function getClassHttpVerbs($controllerClass): array
    {
        if (method_exists($controllerClass, 'getRouteVerbs')) {
            $verbs = $this->getContainer()->call([$controllerClass, 'getRouteVerbs']) ?? $verbs;
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