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

namespace App\Base\Controllers\Admin;

use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminPage;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Logout" Admin Page
 */
class Logout extends AdminPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getTemplateData(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return AdminPage|RedirectResponse|Response
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

        return $this->doRedirect($this->getUrl("admin.login"), [
            "Authorization" => null,
            "Set-Cookie" => "Authorization=;path=/;expires=" . date("r", time() - 3600)
        ]);
    }
}
