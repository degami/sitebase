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

namespace App\Base\Abstracts\Controllers;

use App\Site\Routing\Web;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Base\Abstracts\ContainerAwareObject;
use App\Site\Routing\RouteInfo;
use App\Base\Exceptions\PermissionDeniedException;

/**
 * Base for all controllers
 */
abstract class BasePage extends ContainerAwareObject
{
    /**
     * @var Response response object
     */
    protected Response $response;

    /**
     * BasePage constructor.
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container);
        $this->request = $request ?: $this->getApp()->getRequest();
        $this->route_info = $route_info ?: $this->getAppRouteInfo();

        // dispatch "request_created" event
        $this->getApp()->event('request_created', ['request' => $this->request]);
        $this->response = $this->containerMake(Response::class);

        // let App know this is the controller object
        $this->getAppRouteInfo()?->setControllerObject($this);
    }

    /**
     * controller entrypoint
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response|self
     * @throws PermissionDeniedException
     */
    public function renderPage(RouteInfo $route_info = null, $route_data = []): BasePage|Response
    {
        $this->route_info = $route_info;

        $before_result = $this->beforeRender();
        if ($before_result instanceof Response) {
            return $before_result;
        }

        return $this->process($route_info, $route_data);
    }


    /**
     * gets route data
     *
     * @param null $var_name
     * @return mixed
     */
    protected function getRouteData($var_name = null): mixed
    {
        if (is_null($this->route_info)) {
            return null;
        }

        if ($var_name == null) {
            return $this->getRouteInfo()->getVars();
        }

        $vars = $this->getRouteInfo()->getVars();
        return is_array($vars) && isset($vars[$var_name]) ? $vars[$var_name] : null;
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     */
    protected function beforeRender(): BasePage|Response
    {
        if (
            method_exists($this, 'getAccessPermission') &&
            method_exists($this, 'checkPermission') &&
            method_exists($this, 'getCurrentUser')
        ) {
            if (!$this->checkPermission($this->getAccessPermission())) {
                throw new PermissionDeniedException();
            }
        }

        return $this;
    }

    /**
     * get request object
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * get response object
     *
     * @return Response
     */
    protected function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * get route_info
     *
     * @return RouteInfo|null
     */
    public function getRouteInfo(): ?RouteInfo
    {
        return $this->route_info;
    }

    /**
     * set route info
     * 
     * @param RouteInfo $routeInfo
     * @return BasePage
     */
    public function setRouteInfo(RouteInfo $routeInfo) : BasePage
    {
        $this->route_info = $routeInfo;
        return $this;
    }

    /**
     * toString magic method
     *
     * @return string the form html
     */
    public function __toString(): string
    {
        try {
            return get_class($this);
        } catch (Exception $e) {
            return $e->getMessage() . "\n" . $e->getTraceAsString();
        }
    }

    /**
     * gets url by route_name and params
     *
     * @param string $route_name
     * @param array $route_params
     * @return string
     * @throws BasicException
     */
    public function getUrl(string $route_name, $route_params = []): string
    {
        return $this->getWebRouter()->getUrl($route_name, $route_params);
    }

    /**
     * gets current route name
     *
     * @return string
     */
    public function getRouteName(): string
    {
        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", strtolower(get_class($this))));
        return str_replace("/", ".", trim($path, "/"));
    }

    /**
     * gets current controller url
     *
     * @return string
     * @throws BasicException
     */
    public function getControllerUrl(): string
    {
        if ($this->getRouteInfo() instanceof RouteInfo) {
            return $this->getUrl($this->getRouteInfo()->getRouteName(), $this->getRouteInfo()->getVars());
        }

        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", strtolower(get_class($this))));
        if (method_exists(static::class, 'getRoutePath')) {
            $path = call_user_func([static::class, 'getRoutePath']);
            if (is_string($path)) {
                $path = explode(",", $path);
            }
            $path = reset($path);

            if (method_exists(static::class, 'getRouteGroup')) {
                $path = call_user_func([static::class, 'getRouteGroup']) . '/' . $path;
            }

            $route_vars = [];
            if ($this->getRouteInfo() instanceof RouteInfo) {
                $route_vars = $this->getRouteInfo()->getVars();
            }

            foreach ($route_vars as $var_name => $value) {
                $regexp = "/\{" . $var_name . Web::REGEXP_ROUTEVAR_EXPRESSION . "\}/i";
                $path = preg_replace($regexp, $value, $path);
            }

            return $this->getWebRouter()->getBaseUrl() . $path;
        }

        $route_name = str_replace("/", ".", trim($path, "/"));
        return $this->getUrl($route_name);
    }

    /**
     * gets the destination param
     *
     * @param null $destination_url
     * @return string
     * @throws BasicException
     */
    public function getDestParam($destination_url = null): string
    {
        if (empty($destination_url)) {
            $destination_url = $this->getControllerUrl();
        }
        return urlencode(base64_encode($destination_url . ':' . sha1($this->getEnv('SALT'))));
    }

    /**
     * specifies if this controller is eligible for full page cache
     *
     * @return bool
     */
    public function canBeFPC(): bool
    {
        return false;
    }

    /**
     * returns a redirect object
     *
     * @param $url
     * @param array $additional_headers
     * @return RedirectResponse
     */
    protected function doRedirect($url, $additional_headers = []): RedirectResponse
    {
        return new RedirectResponse(
            $url,
            302,
            array_merge(
                ["Set-Cookie" => $this->getResponse()->headers->get("Set-Cookie")],
                $additional_headers
            )
        );
    }

    /**
     * controller action
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     */
    abstract public function process(RouteInfo $route_info = null, $route_data = []): Response;
}
