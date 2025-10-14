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

namespace App\Base\Abstracts\Controllers;

use App\Base\Traits\FrontendPageTrait;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base for admin pages
 */
abstract class LoggedUserPage extends FrontendPage
{
    use FrontendPageTrait;

    /**
     * @var string page title
     */
    protected ?string $page_title = null;

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['GET', 'POST'];
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender() : BasePage|Response
    {
        if (!$this->getEnvironment()->getVariable('ENABLE_LOGGEDPAGES')) {
            throw new PermissionDeniedException();
        }

        if (!$this->checkCredentials() || !$this->checkPermission(static::getAccessPermission())) {
            throw new PermissionDeniedException();
        }

        return parent::beforeRender();
    }

    /**
     * gets page title
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->page_title ?? ucwords(str_replace("_", " ", implode("", array_slice(explode("\\", get_class($this)), -1, 1))));
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
     * gets route group
     *
     * @return string|null
     */
    public static function getRouteGroup(): ?string
    {
        return (trim(getenv('LOGGEDPAGES_GROUP')) != null) ? '/' . getenv('LOGGEDPAGES_GROUP') : null;
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract public static function getAccessPermission(): string;
}
