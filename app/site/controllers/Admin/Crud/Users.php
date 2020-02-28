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
namespace App\Site\Controllers\Admin\Crud;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\AdminRestPage;
use \App\Site\Models\User as UserModel;
use \App\Site\Routing\RouteInfo;

/**
 * Users REST endpoint
 */
class Users extends AdminRestPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'crud/users[/{id:\d+}]';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_users';
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public static function getObjectClass()
    {
        return UserModel::class;
    }
}
