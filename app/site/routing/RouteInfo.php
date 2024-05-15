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

use FastRoute\Dispatcher;
use App\Base\Abstracts\Controllers\BasePage;

/**
 * Route Information Class
 */
class RouteInfo
{
    /**
     * @var array dispatcher info
     */
    protected array $dispatcher_info;

    /**
     * @var int dispatcher status
     */
    protected $status;

    /**
     * @var mixed handler to invoke
     */
    protected $handler;

    /**
     * @var mixed allowed methods
     */
    protected $allowed_methods;

    /**
     * @var mixed vars
     */
    protected $vars;

    /**
     * @var string uri
     */
    protected string $uri;

    /**
     * @var string http method
     */
    protected string $http_method;

    /**
     * @var string route
     */
    protected string $route;

    /**
     * @var string|null route name
     */
    protected ?string $route_name;

    /**
     * @var int|null rewrite id
     */
    protected ?int $rewrite;

    /**
     * @var string|null type
     */
    protected ?string $type;

    /**
     * @var BasePage|null controller object instance
     */
    protected ?BasePage $controller_object = null;

    /**
     * class constructor
     *
     * @param array $dispatcher_info
     * @param string $http_method
     * @param string $uri
     * @param string $route
     * @param string|null $route_name
     * @param int|null $rewrite
     */
    public function __construct(array $dispatcher_info, string $http_method, string $uri, string $route, string $route_name = null, $rewrite = null, $type = null)
    {
        $this->dispatcher_info = $dispatcher_info;
        $this->http_method = $http_method;
        $this->uri = $uri;
        $this->route = $route;
        $this->status = $dispatcher_info[0];
        $this->route_name = $route_name;
        $this->rewrite = $rewrite;
        $this->type = $type;

        switch ($this->status) {
            case Dispatcher::NOT_FOUND:
                $this->handler = null;
                $this->vars = null;
                $this->allowed_methods = null;
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $this->handler = null;
                $this->vars = null;
                $this->allowed_methods = $dispatcher_info[1];
                break;
            case Dispatcher::FOUND:
                $this->handler = $dispatcher_info[1];
                $this->vars = $dispatcher_info[2];
                $this->allowed_methods = null;
                break;
        }
    }

    /**
     * gets dispatcher info
     *
     * @return array
     */
    public function getDispatcherInfo(): array
    {
        return $this->dispatcher_info;
    }

    /**
     * sets dispatcher info
     *
     * @param array $dispatcher_info
     * @return self
     */
    public function setDispatcherInfo(array $dispatcher_info): RouteInfo
    {
        $this->dispatcher_info = $dispatcher_info;

        return $this;
    }

    /**
     * gets handler to call
     *
     * @return mixed
     */
    public function getHandler(): mixed
    {
        return $this->handler;
    }

    /**
     * sets handler to call
     *
     * @param callback $handler
     * @return self
     */
    public function setHandler(callable $handler): RouteInfo
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * get router vars
     *
     * @return mixed
     */
    public function getVars(): mixed
    {
        return $this->vars;
    }

    /**
     * get router var by name
     * @param string $name
     *
     * @return mixed
     */
    public function getVar(string $name): mixed
    {
        return $this->vars[$name] ?? null;
    }

    /**
     * set router vars
     *
     * @param mixed $vars
     * @return self
     */
    public function setVars(mixed $vars): RouteInfo
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * get allowed methods
     *
     * @return mixed
     */
    public function getAllowedMethods(): mixed
    {
        return $this->allowed_methods;
    }

    /**
     * sets allowed methods
     *
     * @param mixed $allowed_methods
     *
     * @return self
     */
    public function setAllowedMethods(mixed $allowed_methods): RouteInfo
    {
        $this->allowed_methods = $allowed_methods;

        return $this;
    }

    /**
     * gets status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * sets status
     *
     * @param int $status
     *
     * @return self
     */
    public function setStatus(int $status): RouteInfo
    {
        $this->status = $status;

        return $this;
    }

    /**
     * gets http method
     *
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->http_method;
    }

    /**
     * sets http method
     *
     * @param string $http_method
     *
     * @return self
     */
    public function setHttpMethod(string $http_method): RouteInfo
    {
        $this->http_method = $http_method;

        return $this;
    }

    /**
     * gets uri
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * sets uri
     *
     * @param string $uri
     *
     * @return self
     */
    public function setUri(string $uri): RouteInfo
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * gets route
     *
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * sets route
     *
     * @param string $route
     *
     * @return self
     */
    public function setRoute(string $route): RouteInfo
    {
        $this->route = $route;

        return $this;
    }

    /**
     * gets route name
     *
     * @return string|null
     */
    public function getRouteName(): ?string
    {
        return $this->route_name;
    }

    /**
     * sets route name
     *
     * @param string $route_name
     *
     * @return self
     */
    public function setRouteName(string $route_name): RouteInfo
    {
        $this->route_name = $route_name;

        return $this;
    }

    /**
     * gets rewrite
     *
     * @return int|null
     */
    public function getRewrite(): ?int
    {
        return $this->rewrite;
    }

    /**
     * sets rewrite
     *
     * @param int $rewrite
     * @return self
     */
    public function setRewrite(int $rewrite): RouteInfo
    {
        $this->rewrite = $rewrite;

        return $this;
    }

    /**
     * gets type
     *
     * @return int|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * sets type
     *
     * @param int $rewrite
     * @return self
     */
    public function setType(string $type): RouteInfo
    {
        $this->type = $type;

        return $this;
    }


    /**
     * checks if route works also if site is offline
     *
     * @return bool
     */
    public function worksOffline(): bool
    {
        return $this->isAdminRoute();
    }

    /**
     * checks if route is administrative route
     *
     * @return bool
     */
    public function isAdminRoute(): bool
    {
        return $this->getRouteName() == 'admin.login' || preg_match("/^admin/", $this->getRouteName());
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function toString(): string
    {
        return implode("::", $this->handler) . "(" . serialize($this->vars) . ")";
    }

    /**
     * gets controller object
     *
     * @return BasePage|null controller object instance
     */
    public function getControllerObject(): ?BasePage
    {
        return $this->controller_object;
    }

    /**
     * sets controller object
     *
     * @param BasePage $controller_object
     * @return self
     */
    public function setControllerObject(BasePage $controller_object): RouteInfo
    {
        $this->controller_object = $controller_object;

        return $this;
    }

    /**
     * gets RouteInfo data
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'dispatcher_info' => $this->dispatcher_info,
            'status' => $this->status,
            'handler' => $this->handler,
            'allowed_methods' => $this->allowed_methods,
            'vars' => $this->vars,
            'uri' => $this->uri,
            'http_method' => $this->http_method,
            'route' => $this->route,
            'route_name' => $this->route_name,
            'rewrite' => $this->rewrite,
        ];
    }
}
