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
use App\Site\Models\Page;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Models\Rewrite;
use App\Base\Routing\RouteInfo;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\App;

/**
 * Block Preview Admin Callback
 */
class GetBlockPreview extends AdminJsonPage
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
        /** @var Block $block */
        $block = Block::load($this->getRequest()->get('block_id'));
        /** @var Page $page */
        $page = Page::load($this->getRequest()->get('page_id'));

        $app = App::getInstance();

        $currentPage = null;

        $rewrite = $page->getRewrite();
        if ($rewrite) {
            $currentPage = $page->getControllerUsingRewrite($app);
        }

        return ['data' => $block->renderHTML($currentPage)];
    }
}
