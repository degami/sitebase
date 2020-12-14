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

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\AdminPage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Logout" Admin Page
 */
class Logout extends AdminPage
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
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
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
     * @return AdminPage|RedirectResponse|Response
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

        return $this->doRedirect($this->getUrl("admin.login"), [
            "Authorization" => null,
            "Set-Cookie" => "Authorization=;expires=" . date("r", time() - 3600)
        ]);
    }
}
