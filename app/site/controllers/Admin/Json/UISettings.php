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

namespace App\Site\Controllers\Admin\Json;

use App\Base\Abstracts\Controllers\AdminJsonPage;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use App\Site\Models\User;

/**
 * UISettings Admin
 */
class UISettings extends AdminJsonPage
{
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $settings = $this->getSettingsData($this->getRequest());
        if (empty($settings) || !isset($settings['currentRoute'])) {
            throw new Exception("Missing settings content");
        }
    
        $currentRoute = $settings['currentRoute'];
        // remove unuseful element
        unset($settings['currentRoute']);

        /** @var User $user */
        $user = $this->getCurrentUser();
        $uiSettings = $user->getUserSession()->getSessionKey('uiSettings') ?? [];

        // merge incoming data into existing
        $uiSettings[$currentRoute] = array_merge(
            $uiSettings[$currentRoute] ?? [],
            $settings
        );

        // save data
        $user->getUserSession()->addSessionData('uiSettings', $uiSettings)->persist();

        return ['success' => true, 'settings' => $user->getUserSession()->getSessionKey('uiSettings')];
    }

    /**
     * @return string|null
     */
    protected function getSettingsData(Request $request) : ?array
    {
        $content = json_decode($request->getContent(), true);
        if (is_array($content) && !empty($content)) {
            return $content;
        }

        return null;
    }
}
