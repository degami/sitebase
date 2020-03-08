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
use \App\Site\Models\News as NewsModel;

/**
 * News REST endpoint
 */
class News extends AdminRestPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'crud/news[/{id:\d+}]';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_news';
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public static function getObjectClass()
    {
        return NewsModel::class;
    }
}
