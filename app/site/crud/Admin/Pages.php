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
use App\Site\Models\Page as PageModel;

/**
 * Pages REST endpoint
 */
class Pages extends AdminRestPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'pages[/{id:\d+}]';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_pages';
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return PageModel::class;
    }
}
