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

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\LoggedUserPage;
use \App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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
    protected function getTemplateName(): string
    {
        return 'index';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'index';
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
     * @throws PermissionDeniedException
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
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return BasePage|RedirectResponse|Response
     * @throws BasicException
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        return $this->doRedirect($this->getUrl('frontend.users.profile'));
    }
}
