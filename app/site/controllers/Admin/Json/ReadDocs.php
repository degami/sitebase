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

namespace App\Site\Controllers\Admin\Json;

use App\App;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * ReadDocs Admin
 */
class ReadDocs extends AdminJsonPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException)
     */
    protected function getJsonData(): array
    {
        $page = $this->getRequest()->query->get('docpage');

        $parsed = parse_url($page);
        $localPage = App::getDir(App::WEBROOT).str_replace("/", DS, $parsed['path']);
        $pageContents = null;

        if (file_exists($localPage)) {
            $pageContents = "<iframe style=\"width: 100%; height: 100%;\" src=\"$page\"></iframe>";
        }

        return ['success' => true, 'html' => $pageContents];
    }
}