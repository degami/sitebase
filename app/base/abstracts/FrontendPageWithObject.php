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

use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Site\Routing\RouteInfo;
use \Exception;

/**
 * Base for a page diplaying a model
 */
abstract class FrontendPageWithObject extends FrontendPage
{
    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * @var RouteInfo route info object
     */
    protected $route_info = null;

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return '';
    }

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $return = parent::process($route_info, $route_data);

        if (!getenv('DEBUG')) {
            if (!($this->templateData['object'] instanceof Model && $this->templateData['object']->isLoaded())) {
                return $this->getUtils()->errorPage(404);
            }
        }

        if (!isset($this->templateData['object'])) {
            throw new Exception('Missing "object" property');
        }

        return $return;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        if (isset($this->templateData['object']) && ($this->templateData['object'] instanceof Model) && $this->templateData['object']->isLoaded()) {
            if ($this->templateData['object']->getLocale()) {
                return $this->getApp()->setCurrentLocale($this->templateData['object']->getLocale())->getCurrentLocale();
            }
        }

        return $this->getApp()->setCurrentLocale(parent::getCurrentLocale())->getCurrentLocale();
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * sets object to show
     *
     * @param Model $object
     */
    protected function setObject(Model $object)
    {
        $this->templateData['object'] = $object;
        return $this;
    }

    /**
     * gets object to show
     *
     * @return Model|null
     */
    protected function getObject()
    {
        return $this->templateData['object'] ?? null;
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    public function getTranslations()
    {
        return array_map(
            function ($el) {
                return $this->getRouting()->getBaseUrl() . $el;
            },
            $this->getContainer()->call([$this->getObject(), 'getTranslations'])
        );
    }

    /**
     * {@inheritdocs}
     *
     * @return boolean
     */
    public function canBeFPC()
    {
        return true;
    }

    /**
     * gets object class name for loading
     *
     * @return string
     */
    abstract public static function getObjectClass();
}
