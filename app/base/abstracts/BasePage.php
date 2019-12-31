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
namespace App\Base\Abstracts;

use \Exception;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \League\Plates\Template\Template;
use \App\App;
use \App\Site\Routing\RouteInfo;
use \App\Site\Models\GuestUser;

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
     * class constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->request = Request::createFromGlobals();
        // dispatch "before_send" event
        $this->getApp()->event(
            'request_created',
            [
            'request' => $this->request
            ]
        );
        $this->response = $this->getContainer()->make(Response::class);
    }

    /**
     * gets current user
     *
     * @return \App\Site\Model\User|\App\Site\Model\GuestUser
     */
    public function getCurrentUser()
    {
        if (!$this->current_user && !$this->getTokenData()) {
            return $this->getContainer()->make(GuestUser::class);
        }

        return $this->current_user;
    }

    /**
     * checks if user is logged in
     *
     * @return boolean
     */
    public function hasLoggedUser()
    {
        return is_object($this->getCurrentUser()) && isset($this->getCurrentUser()->id) && $this->getCurrentUser()->id > 0;
    }

    /**
     * controller entrypoint
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response|self
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $this->route_info = $route_info;

        $before_result = $this->beforeRender();
        if ($before_result instanceof Response) {
            return $before_result;
        }

        return $this;
    }

    /**
     * checks if current user has specified permission
     *
     * @param  string $permission_name
     * @return boolean
     */
    protected function checkPermission($permission_name)
    {
        try {
            return in_array($permission_name, $this->getCurrentUser()->permissions);
        } catch (\Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * before render hook
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if (method_exists($this, 'getAccessPermission') && method_exists($this, 'getCurrentUser')) {
            try {
                if (!$this->checkPermission($this->getAccessPermission())) {
                    return $this->getUtils()->errorPage(403);
                }
            } catch (\Exception $e) {
                $this->getUtils()->logException($e);
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
     * @return array
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
     * @param  string $route_name
     * @param  array  $route_params
     * @return string
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
        $routename = str_replace("/", ".", trim($path, "/"));
        return $routename;
    }

    /**
     * gets current controller url
     *
     * @return string
     */
    public function getControllerUrl()
    {
        $path = str_replace("app/site/controllers/", "", str_replace("\\", "/", strtolower(get_class($this))));
        if (method_exists(static::class, 'getRoutePath')) {
            $path = call_user_func([static::class, 'getRoutePath']);
        }

        $route_vars = [];
        if ($this->getRouteInfo() instanceof RouteInfo) {
            $route_vars = $this->getRouteInfo()->getVars();
        }

        foreach ($route_vars as $varname => $value) {
            $path = preg_replace("/\{".$varname."(:.*?)?\}/", $value, $path);
        }

        $routename = str_replace("/", ".", trim($path, "/"));
        return $this->getUrl($routename);
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
     * @param  string $url
     * @return RedirectResponse
     */
    protected function doRedirect($url)
    {
        return RedirectResponse::create(
            $url,
            302,
            [
            "Set-Cookie" => $this->getResponse()->headers->get("Set-Cookie")
            ]
        );
    }
}
