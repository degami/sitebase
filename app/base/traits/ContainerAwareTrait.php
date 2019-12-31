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

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Base\Exceptions\BasicException;

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
     * @param  string $service_key
     * @return mixed
     */
    protected function getService($service_key)
    {
        if ($this->getContainer() instanceof ContainerInterface) {
            if ($this->getContainer()->has($service_key)) {
                return $this->getContainer()->get($service_key);
            } else {
                throw new BasicException("{$service_key} is not registered", 1);
            }
        }

        throw new BasicException("Container is not ready", 1);
    }

    /**
     * gets app object
     *
     * @return \App\App
     */
    public function getApp()
    {
        return $this->getService('app');
    }

    /**
     * gets log object
     *
     * @return \Monolog\Logger
     */
    public function getLog()
    {
        return $this->getService('log');
    }

    /**
     * gets plates engine object
     *
     * @return \League\Plates\Engine
     */
    public function getTemplates()
    {
        return $this->getService('templates');
    }

    /**
     * gets db object
     *
     * @return \LessQL\Database
     */
    public function getDb()
    {
        return $this->getService('db');
    }

    /**
     * gets PDO object
     *
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->getService('pdo');
    }

    /**
     * gets schema object
     *
     * @return \Degami\SqlSchema\Schema
     */
    public function getSchema()
    {
        return $this->getService('schema');
    }

    /**
     * gets events manager service
     *
     * @return \Gplanchat\EventManager\SharedEventEmitter
     */
    public function getEventManager()
    {
        return $this->getService('event_manager');
    }

    /**
     * gets routing service
     *
     * @return \App\Site\Routing\Web
     */
    public function getRouting()
    {
        return $this->getService('routing');
    }

    /**
     * gets global utils service
     *
     * @return \App\Base\Tools\Utils\Globals
     */
    public function getUtils()
    {
        return $this->getService('utils');
    }

    /**
     * gets site data service
     *
     * @return \App\Base\Tools\Utils\SiteData
     */
    public function getSiteData()
    {
        return $this->getService('site_data');
    }

    /**
     * gets assets manager
     *
     * @return \App\Base\Tools\Assets\Manager
     */
    public function getAssets()
    {
        return $this->getService('assets');
    }

    /**
     * gets guzzle service
     *
     * @return \GuzzleHttp\Client
     */
    public function getGuzzle()
    {
        return $this->getService('guzzle');
    }

    /**
     * gets imagine service
     *
     * @return \Imagine\Gd\Imagine
     */
    public function getImagine()
    {
        return $this->getService('imagine');
    }

    /**
     * gets mailer service
     *
     * @return \App\Base\Tools\Utils\Mailer
     */
    public function getMailer()
    {
        return $this->getService('mailer');
    }

    /**
     * gets SES mailer service
     *
     * @return \Aws\Ses\SesClient
     */
    public function getSesMailer()
    {
        return $this->getService('ses_mailer');
    }

    /**
     * gets SMTP mailer service
     *
     * @return \Swift_Mailer
     */
    public function getSmtpMailer()
    {
        return $this->getService('smtp_mailer');
    }

    /**
     * get cache manager
     *
     * @return \App\Base\Tools\Cache\Manager
     */
    public function getCache()
    {
        return $this->getService('cache');
    }

    /**
     * gets html renderer service
     *
     * @return \App\Base\Tools\Utils\HtmlPartsRenderer
     */
    public function getHtmlRenderer()
    {
        return $this->getService('html_renderer');
    }

    /**
     * gets env variable
     *
     * @param  string $variable
     * @return mixed
     */
    public function getEnv($variable)
    {
        $env = (array)$this->getService('env');
        return $env[$variable] ?? null;
    }
}
