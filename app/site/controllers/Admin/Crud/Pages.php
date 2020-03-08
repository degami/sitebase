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

use \App\Base\Abstracts\Controllers\AdminRestPage;
use \App\Site\Models\Page as PageModel;

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
    public static function getRoutePath()
    {
        return 'crud/pages[/{id:\d+}]';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_pages';
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public static function getObjectClass()
    {
        return PageModel::class;
    }
}
