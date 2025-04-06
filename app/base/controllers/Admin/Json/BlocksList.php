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

namespace App\Base\Controllers\Admin\Json;

use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Models\Block;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Blocks List Admin Callback
 */
class BlocksList extends AdminJsonPage
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
        return ['data' => array_values(array_map(fn ($block) => [
            'id' => $block->getId(), 
            'locale' => $block->getLocale(), 
            'name' => $block->getTitle()
        ], Block::getCollection()->getItems()))];
    }
}
