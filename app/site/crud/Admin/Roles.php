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

namespace App\Site\Crud\Admin;

use App\Base\Abstracts\Controllers\AdminRestPage;
use App\Site\Models\Role as RoleModel;

/**
 * Roles REST endpoint
 */
class Roles extends AdminRestPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'roles[/{id:\d+}]';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getAccessPermission(): string
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
        return RoleModel::class;
    }
}
