<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App;

use App\Site\Models\Rewrite;
use Degami\Basics\Exceptions\BasicException;
use DI\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use Gplanchat\EventManager\Event;
use Dotenv\Dotenv;
use App\Base\Abstracts\ContainerAwareObject;
use App\Site\Models\Website;
use App\Base\Routing\RouteInfo;
use App\Base\Exceptions\OfflineException;
use App\Base\Exceptions\BlockedIpException;
use App\Base\Exceptions\NotFoundException;
use App\Base\Exceptions\NotAllowedException;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Traits\ContainerAwareTrait;
use App\Base\Traits\ToolsTrait;
use App\Base\Traits\TranslatorsTrait;
use Exception;
use Throwable;

/**
 * App class
 */
class App extends ContainerAwareObject
{
    use ContainerAwareTrait;
    use ToolsTrait;
    use TranslatorsTrait;

    public const ROOT = 'root';
    public const APP = 'app';
    public const CONFIG = 'config';
    public const COMMANDS = 'commands';
    public const CONTROLLERS = 'controllers';
    public const MIGRATIONS = 'migrations';
    public const MODELS = 'models';
    public const ROUTING = 'routing';
    public const LOGS = 'logs';
    public const DUMPS = 'dumps';
    public const TMP = 'tmp';
    public const WEBROOT = 'pub';
    public const MEDIA = 'media';
    public const ASSETS = 'assets';
    public const FLAGS = 'flags';
    public const TEMPLATES = 'templates';
    public const TRANSLATIONS = 'translations';
    public const GRAPHQL = 'graphql';

    /**
     * @var string|null current locale
     */
    protected ?string $current_locale = null;

    /**
     * @var array blocked ips list
     */
    protected $blocked_ips = [];

    public static ?App $instance = null;

    /**
     * class constructor
     */
    public function __construct() {
        // do we need php sessions ?
        // session_start();

        try {
            // load environment variables
            $dotenv = Dotenv::create(static::getDir(self::ROOT));
            $dotenv->load();

            $builder = new ContainerBuilder();
            $builder->addDefinitions(static::getDir(self::CONFIG) . DS . 'di.php');

            /**
             * @var ContainerInterface $this ->container
             */
            $this->container = $builder->build();

            if (is_file(static::getDir(self::CONFIG) . DS . 'blocked_ips.php')) {
                $this->blocked_ips = include(static::getDir(self::CONFIG) . DS . 'blocked_ips.php');
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
                $keys = [
                    'DATABASE_HOST',
                    'DATABASE_NAME',
                    'DATABASE_USER',
                    'DATABASE_PASS',
                    'SMTP_HOST',
                    'SMTP_PORT',
                    'SMTP_USER',
                    'SMTP_PASS'
                ];
                foreach ($keys as $key) {
                    unset($_SERVER[$key]);
                    unset($_ENV[$key]);
                }
            }

            $this->getContainer()->set('env', $env_variables);

            if ($this->getEnv('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar['time']->startMeasure('app_construct', 'App construct');
            }

            $this->getTemplates()->addFolder('base', static::getDir(static::TEMPLATES));
            $this->getTemplates()->addFolder('errors', static::getDir(static::TEMPLATES) . DS . 'errors');
            $this->getTemplates()->addFolder('mails', static::getDir(static::TEMPLATES) . DS . 'mails');

            if ($this->getEnv('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar->addCollector($this->getContainer()->get('db_collector'));
                $debugbar->addCollector($this->getContainer()->get('monolog_collector'));
            }

            // let app be visible from everywhere
            $this->getContainer()->set(App::class, $this);
            $this->getContainer()->set('app', $this->getContainer()->get(App::class));

            if ($this->getEnv('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar['time']->stopMeasure('app_construct');
            }

            App::$instance = $this;
        } catch (Exception $e) {
            $response = new Response(
                'Critical: ' . $e->getMessage(),
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
        $routeInfo = null;
        try {
            $website = null;
            if (php_sapi_name() == 'cli-server') {
                $website = $this->containerCall([Website::class, 'load'], ['id' => getenv('website_id')]);
            }

            if ($this->isBlocked($this->getRequest()->getClientIp())) {
                // if blocked stop immediately
                throw new BlockedIpException();
            }

            $current_website_id = $this->getSiteData()->getCurrentWebsiteId();

            // preload configuration
            $this->getSiteData()->preloadConfiguration();

            $redirects = $this->getSiteData()->getRedirects($current_website_id);
            $redirect_key = urldecode($_SERVER['REQUEST_URI']);
            if (isset($redirects[$redirect_key])) {
                // redirect is not needed if site is offline
                if ($this->isSiteOffline()) {
                    throw new OfflineException();
                }

                // redirect to new url
                $response = RedirectResponse::create(
                    $redirects[$redirect_key]['url_to'],
                    $redirects[$redirect_key]['redirect_code']
                );
            } else {
                // continue with execution
                if ($this->getEnv('PRELOAD_REWRITES')) {
                    // preload all rewrites
                    Rewrite::getCollection()->getItems();
                }

                foreach ($this->getRouters() as $router) {

                    if (!$this->containerCall([$this->getService($router), 'isEnabled'])) {
                        continue;
                    }

                    $routeInfo = $this->containerCall(
                        [$this->getService($router), 'getRequestInfo'],
                        [
                            'http_method' => $_SERVER['REQUEST_METHOD'],
                            'request_uri' => $_SERVER['REQUEST_URI'],
                            'domain' => (php_sapi_name() == 'cli-server') ? $website->domain : $this->getSiteData()->currentServerName()
                        ]
                    );

                    if ($routeInfo->getStatus() != Dispatcher::NOT_FOUND) {
                        break;
                    }
                }

                $this->getContainer()->set(RouteInfo::class, $routeInfo);

                switch ($routeInfo->getStatus()) {
                    case Dispatcher::NOT_FOUND:
                        // ... 404 Not Found
                        throw new NotFoundException();
                    case Dispatcher::METHOD_NOT_ALLOWED:
                        // ... 405 Method Not Allowed
                        throw new NotAllowedException();
                    case Dispatcher::FOUND:
                        $handler = $this->getAppRouteInfo()->getHandler();
                        $vars = $this->getAppRouteInfo()->getVars();

                        // inject container into vars
                        //$vars['container'] = $this->getContainer();

                        // inject request object into vars
                        //$vars['request'] = $this->getRequest();

                        // inject routeInfo
                        $vars['route_info'] = $this->getAppRouteInfo();

                        // add route collected data
                        $vars['route_data'] = $this->getAppRouteInfo()->getVars();

                        if ($this->isSiteOffline() && !$routeInfo->worksOffline()) {
                            throw new OfflineException();
                        }

                        if ($this->getEnv('DEBUG')) {
                            $debugbar = $this->getDebugbar();
                            $debugbar['time']->startMeasure('handler_action', implode('::', $handler));
                        }

                        // ... call $handler with $vars
                        $result = $this->containerCall($handler, $vars);
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
            }
        } catch (OfflineException $e) {
            $response = $this->containerCall([$this->getUtils(), 'offlinePage']);
        } catch (BlockedIpException $e) {
            $response = $this->containerCall([$this->getUtils(), 'blockedIpPage']);
        } catch (NotFoundException $e) {
            $response = $this->containerCall([$this->getUtils(), 'errorPage'], ['error_code' => 404, 'route_info' => $this->getAppRouteInfo()]);
        } catch (PermissionDeniedException $e) {
            $response = $this->containerCall([$this->getUtils(), 'errorPage'], ['error_code' => 403, 'route_info' => $this->getAppRouteInfo()]);
        } catch (NotAllowedException $e) {
            $allowedMethods = $this->getAppRouteInfo()->getAllowedMethods();
            $response = $this->containerCall([$this->getUtils(), 'errorPage'], ['error_code' => 405, 'route_info' => $this->getAppRouteInfo(), 'template_data' => ['allowedMethods' => $allowedMethods]]);
        } catch (BasicException | Exception $e) {
            $response = $this->containerCall([$this->getUtils(), 'exceptionPage'], ['exception' => $e, 'route_info' => $this->getAppRouteInfo()]);
        }

        // dispatch "before_send" event
        $this->event('before_send', [
            'app' => $this,
            'response' => $response
        ]);

        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            if ($debugbar['time']->hasStartedMeasure('app_bootstrap')) {
                $debugbar['time']->stopMeasure('app_bootstrap');
            }
        }

        if ($response instanceof Response) {
            $response->send();
        } else {
            // fallback to 404
            $response = $this->containerCall([$this->getUtils(), 'errorPage'], ['error_code' => 404, 'route_info' => $this->getAppRouteInfo()]);
            $response->send();
        }
    }

    /**
     * checks if site is offline
     *
     * @return bool
     */
    protected function isSiteOffline(): bool
    {
        return is_file(static::getDir(static::APP) . DS . 'offline.flag');
    }

    /**
     * checks if ip address is blocked
     *
     * @param string $ip_address
     * @return bool
     */
    public function isBlocked(string $ip_address): bool
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
    public function event(string $event_name, mixed $event_data): App
    {
        $this->getEventManager()->emit(new Event($event_name, $event_data));

        return $this;
    }

    /**
     * gets application directories
     *
     * @return array
     */
    public static function getDirs(): array
    {
        $appPath = dirname(__FILE__);
        $rootPath = dirname($appPath);

        return [
            self::ROOT => $rootPath,
            self::APP => $appPath,
            self::CONFIG => $rootPath . DS . 'config',
            self::COMMANDS => $appPath . DS . 'site' . DS . 'commands',
            self::CONTROLLERS => $appPath . DS . 'site' . DS . 'controllers',
            self::MIGRATIONS => $appPath . DS . 'site' . DS . 'migrations',
            self::MODELS => $appPath . DS . 'site' . DS . 'models',
            self::ROUTING => $appPath . DS . 'site' . DS . 'routing',
            self::LOGS => $rootPath . DS . 'var' . DS . 'log',
            self::DUMPS => $rootPath . DS . 'var' . DS . 'dumps',
            self::TMP => $rootPath . DS . 'var' . DS . 'tmp',
            self::WEBROOT => $rootPath . DS . 'pub',
            self::MEDIA => $rootPath . DS . 'media',
            self::ASSETS => $rootPath . DS . 'assets',
            self::FLAGS => $rootPath . DS . 'assets' . DS . 'flags',
            self::TEMPLATES => $rootPath . DS . 'templates',
            self::TRANSLATIONS => $rootPath . DS . 'translations',
            self::GRAPHQL => $appPath . DS . 'site' . DS . 'graphql',
        ];
    }

    /**
     * gets application directory by type
     *
     * @param string $type
     * @return string|null
     */
    public static function getDir(string $type): ?string
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
     * @return self
     */
    public function setCurrentLocale(string $locale = null): App
    {
        $this->current_locale = $locale;

        return $this;
    }

    /**
     * gets current locale
     *
     * @return string|null
     */
    public function getCurrentLocale(): ?string
    {
        return $this->current_locale;
    }


    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     * @throws InvalidValueException
     */
    public function __call(string $name, mixed $arguments): mixed
    {
        $method = strtolower(substr(trim($name), 0, 3));
        $prop = self::pascalCaseToSnakeCase(substr(trim($name), 3));
        if ($method == 'get' && $this->getContainer()->has($prop)) {
            return $this->getContainer()->get($prop);
        }

        throw new InvalidValueException("Method \"{$name}\" not found in class\"" . get_class($this) . "\"!", 1);
    }

    /**
     * get current app instance
     * 
     * @return App
     */
    public static function getInstance() : ?App
    {
        return App::$instance;
    }
}
