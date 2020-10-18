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
use \App\Site\Models\Language as LanguageModel;

/**
 * Languages REST endpoint
 */
class Languages extends AdminRestPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'crud/languages[/{id:\d+}]';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_languages';
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public static function getObjectClass()
    {
        return LanguageModel::class;
    }
}
