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
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;

/**
 * Frontend pages Trait
 */
trait FrontendPageTrait
{
    use PageTrait;
    use TemplatePageTrait;

    /**
     * gets route group
     *
     * @return string|null
     */
    public static function getRouteGroup(): ?string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->template_data;
    }

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

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     */
    public function getTranslations(): array
    {
        return array_map(
            function ($el) {
                return $this->getWebRouter()->getBaseUrl() . $el;
            },
            $this->containerCall([$this->getObject(), 'getTranslations'])
        );
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

    protected function check2FA(): bool
    {
        if ($this->getEnv('USE2FA_USERS') && !in_array($this->getRouteInfo()->getRouteName(), ['frontend.users.twofa', 'frontend.users.login']) && ($this->current_user?->passed2fa ?? false) != true) {
            return false;
        }

        return true;
    }

    /**
     *
     * checks user credentials
     *
     * @return bool
     * @throws BasicException
     */
    protected function checkCredentials(): bool
    {
        try {
            if ($this->getTokenData()) {
                $this->current_user_model = $this->getCurrentUser();

                if (!$this->check2FA()) {
                    return false;
                }

                return $this->current_user_model->checkPermission('view_logged_site');
            }
        } catch (Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }
}
