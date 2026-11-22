<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Traits;


use App\App;
use App\Base\Routers\Web;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Environment\Manager as EnvironmentManager;
use App\Base\Abstracts\Models\BaseModel;

/**
 * Frontend pages Trait
 * 
 * @method App getApp()
 * @method EnvironmentManager getEnvironment()
 * @method Web getWebRouter()
 * @method ?BaseModel getObject()
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
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentLocale(): ?string
    {
        return $this->getApp()->setCurrentLocale(parent::getCurrentLocale())->getCurrentLocale();
    }

    /**
     * checks if 2FA is passed
     *
     * @return bool
     */
    protected function check2FA(): bool
    {
        if ($this->getEnvironment()->getVariable('USE2FA_USERS') && !in_array($this->getEnvironment()->getRequestInfo()->getRouteInfo()->getRouteName(), ['frontend.users.twofa', 'frontend.users.login']) && $this->getAuth()->currentUserHasPassed2FA() != true) {
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
