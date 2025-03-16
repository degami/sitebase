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

use App\App;
use App\Base\Abstracts\Controllers\AdminJsonPage;

/**
 * ReadDocs Admin
 */
class ReadDocs extends AdminJsonPage
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
