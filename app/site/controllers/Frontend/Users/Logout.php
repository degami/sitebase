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

namespace App\Site\Controllers\Frontend\Users;

use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\LoggedUserPage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Logout" Logged Page
 */
class Logout extends LoggedUserPage
{
    /**
     * {@inheritdocs}
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
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        return [];
    }

    /**
     * {@inheritdocs}
     *
     * @return LoggedUserPage|RedirectResponse|Response
     * @throws BasicException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function beforeRender()
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
