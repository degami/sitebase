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
use \App\Site\Routing\RouteInfo;

/**
 * "Index" Logged Page
 */
class Index extends LoggedUserPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'index';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'index';
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
     * before render hook
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            return $this->doRedirect($this->getUrl('frontend.users.login'));
        }

        return parent::beforeRender();
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
        return $this->doRedirect($this->getUrl('frontend.users.profile'));
    }
}
