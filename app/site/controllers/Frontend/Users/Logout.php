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

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\Controllers\LoggedUserPage;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Gplanchat\EventManager\Event;

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
    protected function getTemplateName()
    {
        return null;
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'logout';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'view_logged_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return [];
    }

    /**
     * {@inheritdocs}
     *
     * @return Response
     */
    public function beforeRender()
    {
        // dispatch "user_logged_out" event
        $this->getApp()->event(
            'user_logged_out',
            [
            'logged_user' => $this->getCurrentUser(),
            ]
        );

        return RedirectResponse::create(
            $this->getUrl("frontend.user.login"),
            302,
            [
            "Authorization" => null,
            "Set-Cookie" => "Authorization=;expires=".date("r", time()-3600)
            ]
        );
    }
}