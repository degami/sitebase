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
use App\Site\Models\Block as BlockModel;

/**
 * Blocks REST endpoint
 */
class Blocks extends AdminRestPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'blocks[/{id:\d+}]';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_blocks';
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return BlockModel::class;
    }
}
