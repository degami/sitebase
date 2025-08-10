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

use App\Base\Abstracts\Models\BaseModel;

trait FrontendPageWithObjectTrait
{
    use FrontendPageTrait;

    /**
     * sets object to show
     *
     * @param BaseModel $object
     * @return self
     */
    protected function setObject(BaseModel $object) : static
    {
        $this->template_data['object'] = $object;
        return $this;
    }

    /**
     * gets object to show
     *
     * @return BaseModel|null
     */
    public function getObject(): ?BaseModel
    {
        return $this->template_data['object'] ?? null;
    }

        /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentLocale(): string
    {
        if (isset($this->template_data['object']) && ($this->template_data['object'] instanceof BaseModel) && $this->template_data['object']->isLoaded()) {
            if ($this->template_data['object']->getLocale()) {
                return $this->getApp()->setCurrentLocale($this->template_data['object']->getLocale())->getCurrentLocale();
            }
        }

        return $this->getApp()->setCurrentLocale(parent::getCurrentLocale())->getCurrentLocale();
    }

    /**
     * eventually alters template name
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function alterTemplateName(string $templateName): string
    {        
        $alterFunction = '\theme_alterTemplateName'; // to avoid vscode intelephense warning about non existing function
        if (function_exists($alterFunction)) {
            return $alterFunction($templateName, $this);
        }

        if ($this->getObject()?->isLoaded()) {
            if (is_callable([$this->getObject(), 'getTemplateName']) && !empty($this->getObject()->getTemplateName())) {
                return $this->getObject()->getTemplateName();
            }
        }

        return $templateName;
    }
}