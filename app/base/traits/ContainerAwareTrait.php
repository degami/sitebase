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
namespace App\Base\Traits;

use App\App;
use App\Base\Tools\Assets\Manager as AssetsManager;
use App\Base\Tools\Cache\Manager as CacheManager;
use App\Base\Tools\Utils\Globals;
use App\Base\Tools\Utils\HtmlPartsRenderer;
use App\Base\Tools\Utils\Mailer;
use App\Base\Tools\Utils\SiteData;
use App\Site\Routing\Web;
use Aws\Ses\SesClient;
use Degami\SqlSchema\Schema;
use Feather\Icons;
use Gplanchat\EventManager\SharedEventEmitter;
use GuzzleHttp\Client;
use Imagine\Gd\Imagine;
use League\Plates\Engine;
use LessQL\Database;
use Monolog\Logger;
use PDO;
use \Psr\Container\ContainerInterface;
use \Degami\Basics\Exceptions\BasicException;
use Swift_Mailer;

/**
 * Container Aware Object Trait
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface container
     */
    protected $container;

    /**
     * gets container object
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * gets registered service
     *
     * @param string $service_key
     * @return mixed
     * @throws BasicException
     */
    protected function getService($service_key)
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
    public function getApp()
    {
        return $this->getService('app');
    }

    /**
     * gets log object
     *
     * @return Logger
     * @throws BasicException
     */
    public function getLog()
    {
        return $this->getService('log');
    }

    /**
     * gets plates engine object
     *
     * @return Engine
     * @throws BasicException
     */
    public function getTemplates()
    {
        return $this->getService('templates');
    }

    /**
     * gets db object
     *
     * @return Database
     * @throws BasicException
     */
    public function getDb()
    {
        return $this->getService('db');
    }

    /**
     * gets PDO object
     *
     * @return PDO
     * @throws BasicException
     */
    public function getPdo()
    {
        return $this->getService('pdo');
    }

    /**
     * gets schema object
     *
     * @return Schema
     * @throws BasicException
     */
    public function getSchema()
    {
        return $this->getService('schema');
    }

    /**
     * gets events manager service
     *
     * @return SharedEventEmitter
     * @throws BasicException
     */
    public function getEventManager()
    {
        return $this->getService('event_manager');
    }

    /**
     * gets routing service
     *
     * @return Web
     * @throws BasicException
     */
    public function getRouting()
    {
        return $this->getService('routing');
    }

    /**
     * gets global utils service
     *
     * @return Globals
     * @throws BasicException
     */
    public function getUtils()
    {
        return $this->getService('utils');
    }

    /**
     * gets site data service
     *
     * @return SiteData
     * @throws BasicException
     */
    public function getSiteData()
    {
        return $this->getService('site_data');
    }

    /**
     * gets assets manager
     *
     * @return AssetsManager
     * @throws BasicException
     */
    public function getAssets()
    {
        return $this->getService('assets');
    }

    /**
     * gets guzzle service
     *
     * @return Client
     * @throws BasicException
     */
    public function getGuzzle()
    {
        return $this->getService('guzzle');
    }

    /**
     * gets imagine service
     *
     * @return Imagine
     * @throws BasicException
     */
    public function getImagine()
    {
        return $this->getService('imagine');
    }

    /**
     * gets mailer service
     *
     * @return Mailer
     * @throws BasicException
     */
    public function getMailer()
    {
        return $this->getService('mailer');
    }

    /**
     * gets SES mailer service
     *
     * @return SesClient
     * @throws BasicException
     */
    public function getSesMailer()
    {
        return $this->getService('ses_mailer');
    }

    /**
     * gets SMTP mailer service
     *
     * @return Swift_Mailer
     * @throws BasicException
     */
    public function getSmtpMailer()
    {
        return $this->getService('smtp_mailer');
    }

    /**
     * get cache manager
     *
     * @return CacheManager
     * @throws BasicException
     */
    public function getCache()
    {
        return $this->getService('cache');
    }

    /**
     * gets html renderer service
     *
     * @return HtmlPartsRenderer
     * @throws BasicException
     */
    public function getHtmlRenderer()
    {
        return $this->getService('html_renderer');
    }

    /**
     * gets icons service
     *
     * @return Icons
     * @throws BasicException
     */
    public function getIcons()
    {
        return $this->getService('icons');
    }

    /**
     * gets env variable
     *
     * @param string $variable
     * @param mixed $default
     * @return mixed
     * @throws BasicException
     */
    public function getEnv($variable, $default = null)
    {
        $env = (array)$this->getService('env');
        return $env[$variable] ?? $default;
    }
}
