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

use App\Base\Tools\Utils\SiteData;
use App\Site\Models\Configuration;
use App\Site\Models\Rewrite;
use Degami\Basics\Exceptions\BasicException;
use DI\ContainerBuilder;
use LessQL\Row;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \FastRoute\Dispatcher;
use \Psr\Container\ContainerInterface;
use \Gplanchat\EventManager\Event;
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
use \Throwable;

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

            $builder = new ContainerBuilder();
            $builder->addDefinitions($this->getDir(self::CONFIG) . DS . 'di.php');

            /**
             * @var ContainerInterface $this->container
             */
            parent::__construct($builder->build());

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

            $env_variables = array_combine(
                $dotenv->getEnvironmentVariableNames(),
                array_map(
                    'getenv',
                    $dotenv->getEnvironmentVariableNames()
                )
            );

            // remove some sensible data from _SERVER
            if (!getenv('DEBUG')) {
                foreach ([
                             'DATABASE_HOST',
                             'DATABASE_NAME',
                             'DATABASE_USER',
                             'DATABASE_PASS',
                             'SMTP_HOST',
                             'SMTP_PORT',
                             'SMTP_USER',
                             'SMTP_PASS'
                         ] as $key) {
                    unset($_SERVER[$key]);
                    unset($_ENV[$key]);
                }
            }

            $this->getContainer()->set(
                'env',
                $env_variables
            );

            if ($this->getEnv('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar['time']->startMeasure('app_construct', 'App construct');
            }

            $this->getTemplates()->addFolder('base', static::getDir(static::TEMPLATES));
            $this->getTemplates()->addFolder('errors', static::getDir(static::TEMPLATES).DS.'errors');

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

            if ($this->getEnv('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar['time']->stopMeasure('app_construct');
            }
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
     * @throws BasicException
     * @throws Throwable
     */
    public function bootstrap()
    {
        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->startMeasure('app_bootstrap', 'App bootstrap');
        }

        $response = null;
        $request = Request::createFromGlobals();
        try {
            $website = null;
            if (php_sapi_name() == 'cli-server') {
                $website = $this->getContainer()->call([Website::class, 'load'], ['id' => getenv('website_id')]);
            }

            if ($this->getEnv('PRELOAD_REWRITES')) {
                // preload all rewrites
                $this->getContainer()->call([Rewrite::class, 'all']);
            }

            // preload configuration
            $cached_configuration = [];
            // $results = $this->getDb()->table('configuration')->fetchAll();

            $results = $this->getContainer()->call([Configuration::class, 'all']);
            foreach ($results as $result) {
                $cached_configuration[$result->website_id][$result->path][$result->locale ?? 'default'] = $result->value;
            }
            $this->getCache()->set(SiteData::CONFIGURATION_CACHE_KEY, $cached_configuration);

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
                case Dispatcher::METHOD_NOT_ALLOWED:
                    // ... 405 Method Not Allowed
                    throw new NotAllowedException();
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

                    if ($this->getEnv('DEBUG')) {
                        $debugbar = $this->getDebugbar();
                        $debugbar['time']->startMeasure('handler_action', implode('::', $handler));
                    }

                    // ... call $handler with $vars
                    $result = $this->getContainer()->call($handler, $vars);
                    if ($result instanceof Response) {
                        $response = $result;
                    } else {
                        $response = new Response((string)$result, 200);
                    }

                    if ($this->getEnv('DEBUG')) {
                        $debugbar = $this->getDebugbar();
                        if ($debugbar['time']->hasStartedMeasure('handler_action')) {
                            $debugbar['time']->stopMeasure('handler_action');
                        }
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
        } catch (BasicException $e) {
            $response = $this->getUtils()->exceptionPage($e, $request);
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

        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            if ($debugbar['time']->hasStartedMeasure('app_bootstrap')) {
                $debugbar['time']->stopMeasure('app_bootstrap');
            }
        }

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
     * @param $ip_address
     * @return bool
     */
    public function isBlocked($ip_address)
    {
        return in_array($ip_address, $this->blocked_ips);
    }

    /**
     * emits an events
     *
     * @param string $event_name
     * @param mixed $event_data
     * @return self
     * @throws BasicException
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
     * @return App
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
     * @return App
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
     * @throws BasicException
     */
    public function getCurrentWebsiteId()
    {
        return $this->getSiteData()->getCurrentWebsiteId();
    }
}
