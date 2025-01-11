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

namespace App\Site\Crud\Admin;

use App\Base\Abstracts\Controllers\AdminRestPage;
use App\Site\Models\Permission as PermissionModel;

/**
 * Permissions REST endpoint
 */
class Permissions extends AdminRestPage
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled() : bool
    {
        return \App\App::getInstance()->getEnv('CRUD');
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'permissions[/{id:\d+}]';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_permissions';
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return PermissionModel::class;
    }
}
