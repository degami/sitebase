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
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\BaseHtmlPage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login As User Page
 */
class LoginAs extends BaseHtmlPage
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
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'login_as/{token:.*}';
    }

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
     * @return bool
     */
    public function showMenu(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function showBlocks(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return Login|RedirectResponse|Response
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        $token = $this->getRouteInfo()->getVar('token');
        if (empty($token)) {
            throw new PermissionDeniedException('Token missing');
        }

        return $this->doRedirect($this->getUrl("admin.dashboard"), [
            "Set-Cookie" => "Authorization=" . $token . ";path=/",
            "Authorization" => $token,
        ]);
    }

    /**
     * specifies if this controller is eligible for full page cache
     *
     * @return bool
     */
    public function canBeFPC(): bool
    {
        return false;
    }
}
