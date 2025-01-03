<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Controllers\Frontend\Users;

use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\LoggedUserPage;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Logout" Logged Page
 */
class Logout extends LoggedUserPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return '';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'logout';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return BasePage|Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function beforeRender() : BasePage|Response
    {
        // dispatch "user_logged_out" event
        $this->getApp()->event('user_logged_out', [
            'logged_user' => $this->getCurrentUser(),
        ]);

        return $this->doRedirect($this->getUrl("frontend.user.login"), [
            "Authorization" => null,
            "Set-Cookie" => "Authorization=;expires=" . date("r", time() - 3600)
        ]);
    }
}
