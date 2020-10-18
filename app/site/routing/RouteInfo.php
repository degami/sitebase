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
use \App\Base\Abstracts\Controllers\BasePage;

/**
 * Route Information Class
 */
class RouteInfo
{
    /**
     * @var array dispatcher info
     */
    protected $dispatcher_info;

    /**
     * @var integer dispatcher status
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
    protected $uri;

    /**
     * @var string http method
     */
    protected $http_method;

    /**
     * @var string route
     */
    protected $route;

    /**
     * @var string route name
     */
    protected $route_name;

    /**
     * @var integer|null rewrite id
     */
    protected $rewrite;

    /**
     * @var BasePage|null controller object instance
     */
    protected $controller_object = null;

    /**
     * class constructor
     *
     * @param array $dispatcher_info
     * @param string $http_method
     * @param string $uri
     * @param string $route
     * @param string|null $route_name
     * @param integer|null $rewrite
     */
    public function __construct(array $dispatcher_info, string $http_method, string $uri, string $route, string $route_name = null, $rewrite = null)
    {
        $this->dispatcher_info = $dispatcher_info;
        $this->http_method = $http_method;
        $this->uri = $uri;
        $this->route = $route;
        $this->status = $dispatcher_info[0];
        $this->route_name = $route_name;
        $this->rewrite = $rewrite;

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
    public function getDispatcherInfo()
    {
        return $this->dispatcher_info;
    }

    /**
     * sets dispatcher info
     *
     * @param array $dispatcher_info
     * @return self
     */
    public function setDispatcherInfo($dispatcher_info)
    {
        $this->dispatcher_info = $dispatcher_info;

        return $this;
    }

    /**
     * gets handler to call
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * sets handler to call
     *
     * @param mixed $handler
     * @return self
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * get router vars
     *
     * @return mixed
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * get router var by name
     * @param string $varname
     *
     * @return mixed
     */
    public function getVar($varname)
    {
        return $this->vars[$varname] ?? null;
    }

    /**
     * set router vars
     *
     * @param mixed $vars
     * @return self
     */
    public function setVars($vars)
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * get allowed methods
     *
     * @return mixed
     */
    public function getAllowedMethods()
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
    public function setAllowedMethods($allowed_methods)
    {
        $this->allowed_methods = $allowed_methods;

        return $this;
    }

    /**
     * gets status
     *
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * sets status
     *
     * @param mixed $status
     *
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * gets http method
     *
     * @return string
     */
    public function getHttpMethod()
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
    public function setHttpMethod(string $http_method)
    {
        $this->http_method = $http_method;

        return $this;
    }

    /**
     * gets uri
     *
     * @return string
     */
    public function getUri()
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
    public function setUri(string $uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * gets route
     *
     * @return string
     */
    public function getRoute()
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
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * gets route name
     *
     * @return string
     */
    public function getRouteName()
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
    public function setRouteName($route_name)
    {
        $this->route_name = $route_name;

        return $this;
    }

    /**
     * gets rewrite
     *
     * @return integer|null
     */
    public function getRewrite()
    {
        return $this->rewrite;
    }

    /**
     * sets rewrite
     *
     * @param integer $rewrite
     * @return self
     */
    public function setRewrite($rewrite)
    {
        $this->rewrite = $rewrite;

        return $this;
    }

    /**
     * checks if route works also if site is offline
     *
     * @return boolean
     */
    public function worksOffline()
    {
        return $this->isAdminRoute();
    }

    /**
     * checks if route is administrative route
     *
     * @return boolean
     */
    public function isAdminRoute()
    {
        return $this->getRouteName() == 'admin.login' || preg_match("/^admin/", $this->getRouteName());
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function toString()
    {
        return implode("::", $this->handler) . "(" . serialize($this->vars) . ")";
    }

    /**
     * gets controller object
     *
     * @return BasePage|null controller object instance
     */
    public function getControllerObject()
    {
        return $this->controller_object;
    }

    /**
     * sets controller object
     *
     * @param BasePage|null controller object instance $controller_object
     * @return self
     */
    public function setControllerObject(BasePage $controller_object)
    {
        $this->controller_object = $controller_object;

        return $this;
    }

    /**
     * gets routeinfo data
     *
     * @return array
     */
    public function getData()
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
