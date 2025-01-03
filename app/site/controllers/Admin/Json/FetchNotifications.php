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
use App\Site\Models\UserNotification;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Fetch Notifications
 */
class FetchNotifications extends AdminJsonPage
{
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $user = $this->getCurrentUser();

        $notifications = [];

        foreach(UserNotification::getCollection()->where(
            ['user_id' => $user->getId(), 'read' => false],
            ['created_at' => 'ASC']
        )->getItems() as $notification) {
            /** @var UserNotification $notification */
            $notificationArr = json_decode($notification->toJson(), true);
            $notificationArr['sender'] = $notification->getSender()?->getNickname() ?? __('System');
            $notifications[] = $notificationArr;
        }

        return ['notifications' => $notifications];
    }
}
