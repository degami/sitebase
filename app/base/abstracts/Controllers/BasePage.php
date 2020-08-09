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
namespace App\Base\Abstracts\Controllers;

use Degami\Basics\Exceptions\BasicException;
use \Exception;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Site\Routing\RouteInfo;
use \App\Base\Exceptions\PermissionDeniedException;

/**
 * Base for all controllers
 */
abstract class BasePage extends ContainerAwareObject
{
    /**
     * @var Request request object
     */
    protected $request;

    /**
     * @var Response response object
     */
    protected $response;

    /**
     * @var RouteInfo route info object
     */
    protected $route_info = null;

    /**
     * BasePage constructor.
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @throws BasicException
     */
    public function __construct(ContainerInterface $container, Request $request = null)
    {
        parent::__construct($container);
        $this->request = $request ?: Request::createFromGlobals();

        // dispatch "request_created" event
        $this->getApp()->event(
            'request_created',
            [
            'request' => $this->request
            ]
        );
        $this->response = $this->getContainer()->make(Response::class);

        // let App know this is the controller object
        $this->getApp()->getRouteInfo()->setControllerObject($this);
    }

    /**
     * controller entrypoint
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response|self
     * @throws PermissionDeniedException
     */
    public function renderPage(RouteInfo $route_info = null, $route_data = [])
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
     * @param null $varname
     * @return mixed|null
     */
    protected function getRouteData($varname = null)
    {
        if (is_null($this->route_info)) {
            return null;
        }

        if ($varname == null) {
            return $this->getRouteInfo()->getVars();
        }

        $vars = $this->getRouteInfo()->getVars();
        return is_array($vars) && isset($vars[$varname]) ? $vars[$varname] : null;
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     */
    protected function beforeRender()
    {
        if (method_exists($this, 'getAccessPermission') &&
            method_exists($this, 'checkPermission') &&
            method_exists($this, 'getCurrentUser')) {
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
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * get response object
     *
     * @return Response
     */
    protected function getResponse()
    {
        return $this->response;
    }

    /**
     * get route_info array
     *
     * @return RouteInfo
     */
    public function getRouteInfo()
    {
        return $this->route_info;
    }

    /**
     * toString magic method
     *
     * @return string the form html
     */
    public function __toString()
    {
        try {
            return get_class($this);
        } catch (Exception $e) {
            return $e->getMessage()."\n".$e->getTraceAsString();
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
    public function getUrl($route_name, $route_params = [])
    {
        return $this->getRouting()->getUrl($route_name, $route_params);
    }

    /**
     * gets current route name
     *
     * @return string
     */
    public function getRouteName()
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
    public function getControllerUrl()
    {
        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", strtolower(get_class($this))));
        if (method_exists(static::class, 'getRoutePath')) {
            $path = call_user_func([static::class, 'getRoutePath']);
            if (method_exists(static::class, 'getRouteGroup')) {
                $path = call_user_func([static::class, 'getRouteGroup']).'/'.$path;
            }

            $route_vars = [];
            if ($this->getRouteInfo() instanceof RouteInfo) {
                $route_vars = $this->getRouteInfo()->getVars();
            }

            foreach ($route_vars as $varname => $value) {
                $path = preg_replace("/\{".$varname."(:.*?)?\}/", $value, $path);
            }

            return $this->getRouting()->getBaseUrl().$path;
        }

        $routename = str_replace("/", ".", trim($path, "/"));
        return $this->getUrl($routename);
    }

    /**
     * gets the destination param
     *
     * @param null $destination_url
     * @return string
     * @throws BasicException
     */
    public function getDestParam($destination_url = null)
    {
        if (empty($destination_url)) {
            $destination_url = $this->getControllerUrl();
        }
        return urlencode(base64_encode($destination_url.':'.sha1($this->getEnv('SALT'))));
    }

    /**
     * specifies if this controller is eligible for full page cache
     *
     * @return boolean
     */
    public function canBeFPC()
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
    protected function doRedirect($url, $additional_headers = [])
    {
        return RedirectResponse::create(
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
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response|self
     */
    abstract public function process(RouteInfo $route_info = null, $route_data = []);
}
