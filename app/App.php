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
namespace App;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \FastRoute\Dispatcher;
use \Psr\Container\ContainerInterface;
use \Gplanchat\EventManager\Event;
use \App\Base\Tools\Utils\Globals as GlobalUtils;
use \Dotenv\Dotenv;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Site\Models\Website;
use \App\Site\Routing\RouteInfo;
use \App\Base\Exceptions\OfflineException;
use \App\Base\Exceptions\BlockedIpException;
use \App\Base\Exceptions\NotFoundException;
use \App\Base\Exceptions\NotAllowedException;
use \App\Base\Exceptions\PermissionDeniedException;
use \Exception;

/**
 * App class
 */
class App extends ContainerAwareObject
{
    const ROOT = 'root';
    const APP = 'app';
    const CONFIG = 'config';
    const COMMANDS = 'commands';
    const CONTROLLERS = 'controllers';
    const MIGRATIONS = 'migrations';
    const MODELS = 'models';
    const ROUTING = 'routing';
    const LOGS = 'logs';
    const DUMPS = 'dumps';
    const TMP = 'tmp';
    const WEBROOT = 'pub';
    const MEDIA = 'media';
    const ASSETS = 'assets';
    const FLAGS = 'flags';
    const TEMPLATES = 'templates';
    const TRANSLATIONS = 'translations';

    /**
     * @var Dispatcher dispatcher
     */
    protected $dispatcher;

    /**
     * @var string current locale
     */
    protected $current_locale = null;

    /**
     * @var RouteInfo route info
     */
    protected $route_info = null;

    /**
     * @var array blocked ips list
     */
    protected $blocked_ips = [];

    /**
     * class constructor
     */
    public function __construct()
    {
        // do we need php sessions ?
        // session_start();

        try {
            // load environment variables
            $dotenv = Dotenv::create($this->getDir(self::ROOT));
            $dotenv->load();

            $builder = new \DI\ContainerBuilder();
            $builder->addDefinitions($this->getDir(self::CONFIG) . DS . 'di.php');

            if (is_file($this->getDir(self::CONFIG) . DS . 'blocked_ips.php')) {
                $this->blocked_ips = include($this->getDir(self::CONFIG) . DS . 'blocked_ips.php');
                if (!is_array($this->blocked_ips)) {
                    $this->blocked_ips = [$this->blocked_ips];
                }
                $this->blocked_ips = array_unique($this->blocked_ips);
                $this->blocked_ips = array_filter($this->blocked_ips, function ($el) {
                    return is_string($el);
                });

                // do not block localhost
                foreach (['127.0.0.1', '::1', 'localhost'] as $unblock) {
                    if (in_array($unblock, $this->blocked_ips)) {
                        unset($this->blocked_ips[array_search($unblock, $this->blocked_ips)]);
                    }
                }
            }

            /**
             * @var ContainerInterface $this->container
             */
            $this->container = $builder->build();
            $this->getContainer()->set(
                'env',
                array_combine(
                    $dotenv->getEnvironmentVariableNames(),
                    array_map(
                        'getenv',
                        $dotenv->getEnvironmentVariableNames()
                    )
                )
            );

            $this->getTemplates()->addFolder('base', static::getDir(static::TEMPLATES));
            $this->getTemplates()->addFolder('errors', static::getDir(static::TEMPLATES).DS.'errors');

            /* @var Dispatcher $this->dispatcher */
            $this->dispatcher = $this->getRouting()->getDispatcher();

            // dispatch "dispatcher_ready" event
            $this->event(
                'dispatcher_ready',
                [
                'dispatcher' => $this->dispatcher
                ]
            );

            if ($this->getEnv('DEBUG')) {
                $debugbar = $this->getContainer()->get('debugbar');
                $debugbar->addCollector($this->getContainer()->get('db_collector'));
                $debugbar->addCollector($this->getContainer()->get('monolog_collector'));
            }

            // let app be visible from everywhere
            $this->getContainer()->set('app', $this);
        } catch (Exception $e) {
            $response = new Response(
                'Critical: '.$e->getMessage(),
                500
            );
            $response->send();
            die();
        }
    }

    /**
     * application bootstrap
     *
     * @return Response
     */
    public function bootstrap()
    {
        $response = null;
        $request = Request::createFromGlobals();
        try {
            $website = null;
            if (php_sapi_name() == 'cli-server') {
                $website = $this->getContainer()->call([Website::class, 'load'], ['id' => getenv('website_id')]);
            }


            $routeInfo = $this->getContainer()->call(
                [$this->getRouting(), 'getRequestInfo'],
                [
                'http_method' => $_SERVER['REQUEST_METHOD'],
                'request_uri' => $_SERVER['REQUEST_URI'],
                'domain' => (php_sapi_name() == 'cli-server') ? $website->domain : $this->getSiteData()->currentServerName()
                ]
            );


            $this->setRouteInfo($routeInfo);

            if ($this->isBlocked($request->getClientIp())) {
                // if blocked stop immediately
                throw new BlockedIpException();
            }

            switch ($routeInfo->getStatus()) {
                case Dispatcher::NOT_FOUND:
                    // ... 404 Not Found
                    throw new NotFoundException();
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    // ... 405 Method Not Allowed
                    throw new NotAllowedException();
                    break;
                case Dispatcher::FOUND:
                    $handler = $this->getRouteInfo()->getHandler();
                    $vars = $this->getRouteInfo()->getVars();

                    // inject container into vars
                    $vars['container'] = $this->getContainer();

                    // inject request object into vars
                    $vars['request'] = $request;

                    // inject routeInfo
                    $vars['route_info'] = $this->getRouteInfo();

                    // add route collected data
                    $vars['route_data'] = $this->getRouteInfo()->getVars();

                    if ($this->isSiteOffline() && !$routeInfo->worksOffline()) {
                        throw new OfflineException();
                    }

                    // ... call $handler with $vars
                    $result = $this->getContainer()->call($handler, $vars);
                    if ($result instanceof Response) {
                        $response = $result;
                    } else {
                        $response = new Response((string)$result, 200);
                    }

                    break;
            }
        } catch (OfflineException $e) {
            $response = $this->getUtils()->offlinePage($request);
        } catch (BlockedIpException $e) {
            $response = $this->getUtils()->blockedIpPage($request);
        } catch (NotFoundException $e) {
            $response = $this->getUtils()->errorPage(404, $request);
        } catch (PermissionDeniedException $e) {
            $response = $this->getUtils()->errorPage(403, $request);
        } catch (NotAllowedException $e) {
            $allowedMethods = $this->getRouteInfo()->getAllowedMethods();
            $this->getUtils()->errorPage(405, $request, ['allowedMethods' => $allowedMethods])->send();
        } catch (Exception $e) {
            $response = $this->getUtils()->exceptionPage($e, $request);
        }

        // dispatch "before_send" event
        $this->event(
            'before_send',
            [
            'app' => $this,
            'response' => $response
            ]
        );
        if ($response) {
            $response->send();
        }
    }

    /**
     * checks if site is offline
     *
     * @return boolean
     */
    protected function isSiteOffline()
    {
        return is_file(static::getDir(static::APP).DS.'offline.flag');
    }

    /**
     * checks if ip address is blocked
     *
     * @return boolean
     */
    public function isBlocked($ip_address)
    {
        return in_array($ip_address, $this->blocked_ips);
    }

    /**
     * emits an events
     *
     * @param  string $event_name
     * @param  mixed  $event_data
     * @return self
     */
    public function event($event_name, $event_data)
    {
        $this->getEventManager()->emit(new Event($event_name, $event_data));

        return $this;
    }

    /**
     * gets application directories
     *
     * @return array
     */
    public static function getDirs()
    {
        return [
            self::ROOT => dirname(dirname(__FILE__)),
            self::APP => dirname(__FILE__),
            self::CONFIG => dirname(dirname(__FILE__)) . DS . 'config',
            self::COMMANDS => dirname(__FILE__) . DS . 'site' . DS . 'commands',
            self::CONTROLLERS => dirname(__FILE__) . DS . 'site' . DS . 'controllers',
            self::MIGRATIONS => dirname(__FILE__) . DS . 'site' . DS . 'migrations',
            self::MODELS => dirname(__FILE__) . DS . 'site' . DS . 'models',
            self::ROUTING => dirname(__FILE__) . DS . 'site' . DS . 'routing',
            self::LOGS => dirname(dirname(__FILE__)) . DS . 'var' . DS . 'log',
            self::DUMPS => dirname(dirname(__FILE__)) . DS . 'var' . DS . 'dumps',
            self::TMP => dirname(dirname(__FILE__)) . DS . 'var' . DS . 'tmp',
            self::WEBROOT => dirname(dirname(__FILE__)) . DS . 'pub',
            self::MEDIA => dirname(dirname(__FILE__)) . DS . 'media',
            self::ASSETS => dirname(dirname(__FILE__)) . DS . 'assets',
            self::FLAGS => dirname(dirname(__FILE__)) . DS . 'assets' . DS . 'flags',
            self::TEMPLATES => dirname(dirname(__FILE__)) . DS . 'templates',
            self::TRANSLATIONS => dirname(dirname(__FILE__)) . DS . 'translations',
        ];
    }

    /**
     * gets application directory by type
     *
     * @param  string $type
     * @return string
     */
    public static function getDir($type)
    {
        $dirs = static::getDirs();
        if (!isset($dirs[$type])) {
            return null;
        }

        return $dirs[$type];
    }

    /**
     * sets current locale
     *
     * @param string|null $locale
     */
    public function setCurrentLocale(string $locale = null)
    {
        $this->current_locale = $locale;

        return $this;
    }

    /**
     * gets current locale
     *
     * @return string|null
     */
    public function getCurrentLocale()
    {
        return $this->current_locale;
    }

    /**
     * sets route info
     *
     * @param RouteInfo $route_info
     */
    public function setRouteInfo(RouteInfo $route_info)
    {
        $this->route_info = $route_info;
        return $this;
    }

    /**
     * gets route info
     *
     * @return RouteInfo|null
     */
    public function getRouteInfo()
    {
        return $this->route_info;
    }

    /**
     * gets current website id
     *
     * @return integer
     */
    public function getCurrentWebsiteId()
    {
        return $this->getSiteData()->getCurrentWebsiteId();
    }
}
