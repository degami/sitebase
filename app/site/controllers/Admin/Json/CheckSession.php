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

namespace App\Site\Controllers\Admin\Json;

use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Exceptions\PermissionDeniedException;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Check Admin Session
 */
class CheckSession extends AdminJsonPage
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

        if ($user->locked == true) {
            throw new PermissionDeniedException('User Locked');
        }

        return [
            'user' => $user->getData(),
        ];
    }
}
