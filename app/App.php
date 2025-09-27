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

use App\Base\Models\Rewrite;
use Degami\Basics\Exceptions\BasicException;
use DI\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use Gplanchat\EventManager\Event;
use App\Base\Models\Website;
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
use HaydenPierce\ClassFinder\ClassFinder;
use Throwable;
use App\Base\Interfaces\EventListenerInterface;

/**
 * App class
 */
class App
{
    use ContainerAwareTrait;
    use ToolsTrait;
    use TranslatorsTrait;

    public const ROOT = 'root';
    public const APP = 'app';
    public const CONFIG = 'config';
    public const COMMANDS = 'commands';
    public const CONTROLLERS = 'controllers';
    public const BASE_MIGRATIONS = 'base_migrations';
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

    public const BASE_COMMANDS_NAMESPACE = 'App\Base\Commands';
    public const COMMANDS_NAMESPACE = 'App\Site\Commands';

    public const BASE_CONTROLLERS_NAMESPACE = 'App\Base\Controllers';
    public const CONTROLLERS_NAMESPACE = 'App\Site\Controllers';

    public const BASE_CRON_TASKS_NAMESPACE = 'App\Base\Cron\Tasks';
    public const CRON_TASKS_NAMESPACE = 'App\Site\Cron\Tasks';

    public const BASE_ROUTERS_NAMESPACE = 'App\Base\Routers';
    public const ROUTERS_NAMESPACE = 'App\Site\Routers';

    public const BASE_BLOCKS_NAMESPACE = 'App\Base\Blocks';
    public const BLOCKS_NAMESPACE = 'App\Site\Blocks';

    public const BASE_MODELS_NAMESPACE = 'App\Base\Models';
    public const MODELS_NAMESPACE = 'App\Site\Models';

    public const BASE_CRUD_NAMESPACE = 'App\Base\Crud';
    public const CRUD_NAMESPACE = 'App\Site\Crud';

    public const BASE_COMMERCE_NAMESPACE = 'App\Base\Commerce';
    public const COMMERCE_NAMESPACE = 'App\Site\Commerce';

    public const BASE_MIGRATIONS_NAMESPACE = 'App\Base\Migrations';
    public const MIGRATIONS_NAMESPACE = 'App\Site\Migrations';

    public const BASE_EVENT_LISTENERS_NAMESPACE = 'App\Base\EventListeners';
    public const EVENT_LISTENERS_NAMESPACE = 'App\Site\EventListeners';

    public const WEBHOOKS_NAMESPACE = 'App\Site\Webhooks';
    public const QUEUES_NAMESPACE = 'App\Site\Queues';

    public const GRAPHQL_RESOLVERS_NAMESPACE = 'App\Site\GraphQL\Resolvers';

    public const BASE_AIMODELS_NAMESPACE = 'App\Base\AI\Models';
    public const AIMODELS_NAMESPACE = 'App\Site\AI\Models';

    /**
     * @var string|null current locale
     */
    protected ?string $current_locale = null;

    /**
     * @var array blocked ips list
     */
    protected $blocked_ips = [];

    /**
     * @var App|null application instance
     */
    public static ?App $instance = null;

    /**
     * class constructor
     */
    public function __construct() {
        // do we need php sessions ?
        // session_start();

        try { 
            $builder = new ContainerBuilder();
            $builder->addDefinitions(static::getDir(self::CONFIG) . DS . 'di.php');

            /**
             * @var ContainerInterface $this->container
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

            if ($this->getEnvironment()->getVariable('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar['time']->startMeasure('app_construct', 'App construct');
            }

            $this->getTemplates()->addFolder('base', static::getDir(static::TEMPLATES));
            $this->getTemplates()->addFolder('errors', static::getDir(static::TEMPLATES) . DS . 'errors');
            $this->getTemplates()->addFolder('mails', static::getDir(static::TEMPLATES) . DS . 'mails');

            if ($this->getEnvironment()->getVariable('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar->addCollector($this->getContainer()->get('db_collector'));
                $debugbar->addCollector($this->getContainer()->get('monolog_collector'));
            }

            // let app be visible from everywhere
            $this->getContainer()->set(App::class, $this);
            $this->getContainer()->set('app', $this->getContainer()->get(App::class));

            if ($this->getEnvironment()->getVariable('DEBUG')) {
                $debugbar = $this->getDebugbar();
                $debugbar['time']->stopMeasure('app_construct');
            }

            App::$instance = $this;
        } catch (Throwable $e) {
            if ($this->getEnvironment()->isCli()) {
                echo $e->getMessage() . PHP_EOL;
                die();
            }
            
            $response = new Response(
                $this->genericErrorPage('Critical Error', $e->getMessage()),
                500
            );
            $response->prepare($this->getEnvironment()->getRequest())->send();
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
        if ($this->getEnvironment()->getVariable('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->startMeasure('app_bootstrap', 'App bootstrap');
        }

        $this->registerEventListeners();

        $response = null;
        $routeInfo = null;
        try {
            $website = null;
            if ($this->getEnvironment()->isCliServer()) {
                $website = $this->containerCall([Website::class, 'load'], ['id' => getenv('website_id')]);
            }

            if ($this->isBlocked($this->getEnvironment()->getRequest()?->getClientIp())) {
                // if blocked stop immediately
                throw new BlockedIpException();
            }

            $redirects = [];
            if (App::installDone()) {
                $current_website_id = $this->getSiteData()->getCurrentWebsiteId();

                // preload configuration
                $this->getSiteData()->preloadConfiguration();
    
                if (is_int($current_website_id)) {
                    $redirects = $this->getSiteData()->getRedirects($current_website_id);
                }
            }

            $redirect_key = urldecode($_SERVER['REQUEST_URI']);
            if (isset($redirects[$redirect_key])) {
                // redirect is not needed if site is offline
                if ($this->isSiteOffline()) {
                    throw new OfflineException();
                }

                // redirect to new url
                $response = $this->getUtils()->createRedirectResponse(
                    $redirects[$redirect_key]['url_to'],
                    $redirects[$redirect_key]['redirect_code']
                );
            } else {
                // continue with execution
                if (App::installDone() && $this->getEnvironment()->getVariable('PRELOAD_REWRITES')) {
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

                // register routeinfo into container
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

                        // inject routeInfo
                        $vars['route_info'] = $this->getAppRouteInfo();

                        // add route collected data
                        $vars['route_data'] = $this->getAppRouteInfo()->getVars();
                        if (isset($vars['lang'])) {
                            $this->setCurrentLocale($vars['lang']);
                            if (!isset($vars['locale'])) {
                                $vars['locale'] = $vars['lang'];
                            }
                        }

                        if ($this->isSiteOffline() && !$routeInfo->worksOffline()) {
                            throw new OfflineException();
                        }

                        if ($this->getEnvironment()->getVariable('DEBUG')) {
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

                        if ($this->getEnvironment()->getVariable('DEBUG')) {
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
        } catch (BasicException | Exception | Throwable $e) {
            if (App::installDone()) {
                $response = $this->containerCall([$this->getUtils(), 'exceptionPage'], ['exception' => $e, 'route_info' => $this->getAppRouteInfo()]);
            } else {
                $response = new Response(
                    $this->genericErrorPage('Critical Error', $e->getMessage()),
                    500
                );
            }
        }

        // dispatch "before_send" event
        $this->event('before_send', [
            'app' => $this,
            'response' => $response
        ]);

        if ($this->getEnvironment()->getVariable('DEBUG')) {
            $debugbar = $this->getDebugbar();
            if ($debugbar['time']->hasStartedMeasure('app_bootstrap')) {
                $debugbar['time']->stopMeasure('app_bootstrap');
            }
        }

        if ($response instanceof Response) {
            $response->prepare($this->getEnvironment()->getRequest())->send();
        } else {
            // fallback to 404
            $response = $this->containerCall([$this->getUtils(), 'errorPage'], ['error_code' => 404, 'route_info' => $this->getAppRouteInfo()]);
            $response->prepare($this->getEnvironment()->getRequest())->send();
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

    public function registerEventListeners(bool $reset = false) : App
    {
        $event_liteners_key = "site.event_listeners";
        if (!$this->getCache()->has($event_liteners_key) || $reset) {

            $listeners = array_filter(array_merge(
                ClassFinder::getClassesInNamespace(App::BASE_EVENT_LISTENERS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
                ClassFinder::getClassesInNamespace(App::EVENT_LISTENERS_NAMESPACE, ClassFinder::RECURSIVE_MODE)
            ), fn ($listenerClass) => is_subclass_of($listenerClass, EventListenerInterface::class));

            $this->getCache()->set($event_liteners_key, $listeners);
        } else {
            $listeners = $this->getCache()->get($event_liteners_key);
        }

        foreach ($listeners as $listenerClass) {
            if (is_subclass_of($listenerClass, EventListenerInterface::class)) {
                $lister = $this->containerMake($listenerClass);

                foreach ($lister->getEventHandlers() as $eventName => $eventHandler) {
                    if (is_callable($eventHandler)) {
                        $this->getEventManager()->on([$eventName], function(Event $e) use ($eventHandler) {
                            call_user_func_array($eventHandler, [$e]);
                        });
                    }
                }
            }
        }

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
            self::BASE_MIGRATIONS => $appPath . DS . 'base' . DS . 'migrations',
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
    public function setCurrentLocale(?string $locale = null): App
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

    /**
     * checks if .env file is present
     *
     * @return bool
     */
    public static function dotEnvPresent() : bool
    {
        return is_file(static::getDir(static::ROOT) . DS . '.env') && !empty(file_get_contents(static::getDir(static::ROOT) . DS . '.env'));
    }

    /**
     * checks if .env file has db informations
     *
     * @return bool
     */
    public static function dotEnvHasDbInformations() : bool
    {
        if (!static::dotEnvPresent()) {
            return false;
        }

        $dotenv = parse_ini_file(static::getDir(static::ROOT) . DS . '.env');
        if (empty($dotenv['DATABASE_HOST']) || empty($dotenv['DATABASE_NAME']) || empty($dotenv['DATABASE_USER'])) {
            return false;
        }

        return true;
    }

    /**
     * checks if installation is done
     *
     * @return bool
     */
    public static function installDone() : bool
    {
        // && (is_file(static::getDir(static::ROOT) . DS . '.env') && is_dir(static::getDir(static::ROOT) . DS . 'vendor'))
        return is_file(static::getDir(static::ROOT) . DS . '.install_done');
    }
    
    /**
     * returns a generic error page html
     * 
     * @return string
     */
    protected function genericErrorPage(string $title, string $errorMessage, ?Throwable $t = null) : string
    {
        $traceDetails = "";
        if ($t) {
            $traceDetails = $t->getTraceAsString();
        }
        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 40px;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            font-size: 16px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$title}</h1>
        <p>{$errorMessage}</p>
        {$traceDetails}
    </div>
</body>
</html>
HTML;
    }
}
