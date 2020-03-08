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

use \App\Base\Abstracts\ContainerAwareObject;
use \App\Base\Abstracts\Models\BaseModel;

/**
 * Frontend pages Trait
 */
trait FrontendTrait
{
    use PageTrait;

    /**
     * @var array template data
     */
    protected $templateData = [];

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
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * sets object to show
     *
     * @param BaseModel $object
     */
    protected function setObject(BaseModel $object)
    {
        $this->templateData['object'] = $object;
        return $this;
    }

    /**
     * gets object to show
     *
     * @return BaseModel|null
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
     * @return string
     */
    public function getCurrentLocale()
    {
        if (isset($this->templateData['object']) && ($this->templateData['object'] instanceof BaseModel) && $this->templateData['object']->isLoaded()) {
            if ($this->templateData['object']->getLocale()) {
                return $this->getApp()->setCurrentLocale($this->templateData['object']->getLocale())->getCurrentLocale();
            }
        }

        return $this->getApp()->setCurrentLocale(parent::getCurrentLocale())->getCurrentLocale();
    }
}
