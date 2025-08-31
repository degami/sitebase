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

namespace App\Base\Traits;

use App\App;
use App\Base\Abstracts\Routing\BaseRouter;
use App\Base\AI\Manager as AIManager;
use App\Base\Tools\Assets\Manager as AssetsManager;
use App\Base\Tools\Cache\Manager as CacheManager;
use App\Base\Tools\Redis\Manager as RedisManager;
use App\Base\Tools\Search\Manager as SearchManager;
use App\Base\Tools\ApplicationLogger\Manager as ApplicationLoggerManager;
use App\Base\Auth\Manager as AuthManager;
use App\Base\Tools\Utils\Globals;
use App\Base\Tools\Utils\HtmlPartsRenderer;
use App\Base\Tools\Utils\Mailer;
use App\Base\Tools\Utils\SiteData;
use App\Base\Tools\Utils\Zip;
use App\Base\Models\Website;
use App\Base\Routers\Admin;
use App\Base\Routing\RouteInfo;
use App\Base\Routers\Web;
use App\Base\Routers\Webhooks;
use App\Base\Routers\Crud;
use Aws\Ses\SesClient;
use DebugBar\StandardDebugBar;
use Degami\SqlSchema\Schema;
use Feather\Icons;
use Gplanchat\EventManager\SharedEventEmitter;
use GuzzleHttp\Client as GuzzleHttpClient;
use Imagine\Gd\Imagine;
use League\Plates\Engine;
use LessQL\Database;
use Monolog\Logger;
use PDO;
use Psr\Container\ContainerInterface;
use Degami\Basics\Exceptions\BasicException;
use HaydenPierce\ClassFinder\ClassFinder;
use PHPGangsta_GoogleAuthenticator;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\Request;
use function DI\autowire;
use DI\Container;

/**
 * Container Aware Object Trait
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface container
     */
    protected ContainerInterface $container;

    /**
     * gets container object
     *
     * @return ContainerInterface|Container
     */
    public function getContainer(): ContainerInterface|Container
    {
        return $this->container;
    }

    /**
     * call method through container
     * 
     * @param callable $callable
     * @param array $arguments
     * @return mixed
     */
    public function containerCall(callable|array $callable, array $arguments = []) : mixed
    {
        return $this->getContainer()->call($callable, $arguments);
    }

    /**
     * create object through container
     */
    public function containerMake(string $className, array $arguments = []) : mixed
    {
        return $this->getContainer()->make($className, $arguments);
    }

    /**
     * gets registered service
     *
     * @param string $service_key
     * @return mixed
     * @throws BasicException
     */
    protected function getService(string $service_key): mixed
    {
        if ($this->getContainer() instanceof ContainerInterface) {
            if ($this->getContainer()->has($service_key)) {
                return $this->getContainer()->get($service_key);
            } else {
                throw new BasicException("{$service_key} is not registered ", 1);
            }
        }

        throw new BasicException("Container is not ready", 1);
    }

    /**
     * gets app object
     *
     * @return App
     * @throws BasicException
     */
    public function getApp(): App
    {
        return $this->getService('app');
    }

    /**
     * gets log object
     *
     * @return Logger
     * @throws BasicException
     */
    public function getLog(): Logger
    {
        return $this->getService('log');
    }

    /**
     * gets plates engine object
     *
     * @return Engine
     * @throws BasicException
     */
    public function getTemplates(): Engine
    {
        return $this->getService('templates');
    }

    /**
     * gets debugbar object
     *
     * @return StandardDebugBar
     * @throws BasicException
     */
    public function getDebugbar(): StandardDebugBar
    {
        return $this->getService('debugbar');
    }

    /**
     * gets db object
     *
     * @return Database|null
     * @throws BasicException
     */
    public function getDb(): ?Database
    {
        return $this->getService('db');
    }

    /**
     * gets PDO object
     *
     * @return PDO|null
     * @throws BasicException
     */
    public function getPdo(): ?PDO
    {
        return $this->getService('pdo');
    }

    /**
     * gets schema object
     *
     * @return Schema|null
     * @throws BasicException
     */
    public function getSchema(): ?Schema
    {
        return $this->getService('schema');
    }

    /**
     * gets events manager service
     *
     * @return SharedEventEmitter
     * @throws BasicException
     */
    public function getEventManager(): SharedEventEmitter
    {
        return $this->getService('event_manager');
    }

    /**
     * gets routing service
     *
     * @return Web
     * @throws BasicException
     */
    public function getWebRouter(): Web
    {
        return $this->getService('web_router');
    }

    /**
     * gets admin service
     *
     * @return Admin
     * @throws BasicException
     */
    public function getAdminRouter(): Admin
    {
        return $this->getService('admin_router');
    }

    /**
     * gets crud service
     *
     * @return Crud
     * @throws BasicException
     */
    public function getCrudRouter(): Crud
    {
        return $this->getService('crud_router');
    }

   /**
     * gets webhooks service
     *
     * @return Webhooks
     * @throws BasicException
     */
    public function getWebhooksRouter(): Webhooks
    {
        return $this->getService('webhooks_router');
    }

    /**
     * gets routers
     *
     * @return array
     * @throws BasicException
     */
    public function getRouters(): array
    {
        if (!$this->getContainer()->has('routers')) {
            $out = [];

            $registerRouter = function (string $className) use (&$out) {
                if (is_subclass_of($className, BaseRouter::class)) {
                    $serviceName = strtolower(static::getClassBasename($className)) . '_router'; 
                    $out[] = $serviceName;
    
                    if (!$this->getContainer()->has($serviceName)) {
                        $this->getContainer()->set($serviceName, autowire($className));
                    }
                }
            };


            foreach (ClassFinder::getClassesInNamespace(App::BASE_ROUTERS_NAMESPACE, ClassFinder::RECURSIVE_MODE) as $className) {
                $registerRouter($className);
            }
    
            foreach (ClassFinder::getClassesInNamespace(App::ROUTERS_NAMESPACE, ClassFinder::RECURSIVE_MODE) as $className) {
                $registerRouter($className);
            }

            $this->getContainer()->set('routers', $out);
        }

        return $this->getService('routers');
    }

    /**
     * gets global utils service
     *
     * @return Globals
     * @throws BasicException
     */
    public function getUtils(): Globals
    {
        return $this->getService('utils');
    }

    /**
     * gets site data service
     *
     * @return SiteData
     * @throws BasicException
     */
    public function getSiteData(): SiteData
    {
        return $this->getService('site_data');
    }

    /**
     * gets assets manager
     *
     * @return AssetsManager
     * @throws BasicException
     */
    public function getAssets(): AssetsManager
    {
        return $this->getService('assets');
    }

    /**
     * gets guzzle service
     *
     * @return GuzzleHttpClient
     * @throws BasicException
     */
    public function getGuzzle(): GuzzleHttpClient
    {
        return $this->getService('guzzle');
    }

    /**
     * gets imagine service
     *
     * @return Imagine
     * @throws BasicException
     */
    public function getImagine(): Imagine
    {
        return $this->getService('imagine');
    }

    /**
     * gets mailer service
     *
     * @return Mailer
     * @throws BasicException
     */
    public function getMailer(): Mailer
    {
        return $this->getService('mailer');
    }

    /**
     * gets SES mailer service
     *
     * @return SesClient
     * @throws BasicException
     */
    public function getSesMailer(): SesClient
    {
        return $this->getService('ses_mailer');
    }

    /**
     * gets SMTP mailer service
     *
     * @return Swift_Mailer
     * @throws BasicException
     */
    public function getSmtpMailer(): Swift_Mailer
    {
        return $this->getService('smtp_mailer');
    }

    /**
     * get cache manager
     *
     * @return CacheManager
     * @throws BasicException
     */
    public function getCache(): CacheManager
    {
        return $this->getService('cache');
    }

    /**
     * gets html renderer service
     *
     * @return HtmlPartsRenderer
     * @throws BasicException
     */
    public function getHtmlRenderer(): HtmlPartsRenderer
    {
        return $this->getService('html_renderer');
    }

    /**
     * gets icons service
     *
     * @return Icons
     * @throws BasicException
     */
    public function getIcons(): Icons
    {
        return $this->getService('icons');
    }

    /**
     * gets redis service
     * 
     * @return RedisManager
     * @throws BasicException
     */
    public function getRedis(): RedisManager
    {
        return $this->getService('redis');
    }

    /**
     * gets search service
     * 
     * @return SearchManager
     * @throws BasicException
     */
    public function getSearch(): SearchManager
    {
        return $this->getService('search');
    }

    /**
     * gets zip service
     * 
     * @return Zip
     * @throws BasicException
     */
    public function getZip(): Zip
    {
        return $this->getService('zip');
    }

    /**
     * gets google authenticator service
     * 
     * @return PHPGangsta_GoogleAuthenticator
     * @throws BasicException
     */
    public function getGoogleAuthenticator(): PHPGangsta_GoogleAuthenticator
    {
        return $this->getService('googleauthenticator');
    }

    /**
     * gets application_logger service
     * 
     * @return ApplicationLoggerManager
     * @throws BasicException
     */
    public function getApplicationLogger(): ApplicationLoggerManager
    {
        return $this->getService('application_logger');
    }

    /**
     * gets env variable
     *
     * @param string $variable
     * @param mixed $default
     * @return mixed
     * @throws BasicException
     */
    public function getEnv(string $variable, mixed $default = null)
    {
        $env = (array)$this->getService('env');
        return $env[$variable] ?? $default;
    }

    /**
     * gets current request object
     *
     * @return Request
     * @throws BasicException
     */
    public function getRequest(): Request
    {
        return $this->getService(Request::class);
    }

    /**
     * gets route info
     *
     * @return RouteInfo|null
     */
    public function getAppRouteInfo(): ?RouteInfo
    {
        try {
            return $this->getContainer()->get(RouteInfo::class);
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * gets current website
     *
     * @return Website|null
     */
    public function getAppWebsite(): ?Website
    {
        try {
            return $this->getContainer()->get(Website::class);
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * gets auth manager service
     * 
     * @return AuthManager
     * @throws BasicException
     */
    public function getAuth(): AuthManager
    {
        return $this->getService('auth');
    }

    /**
     * gets ai manager service
     * 
     * @return AIManager
     * @throws BasicException
     */
    public function getAI(): AIManager
    {
        return $this->getService('ai');
    }
}
