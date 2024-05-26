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

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminPage;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * "Phoinfo" Admin Page
 */
class Phpinfo extends AdminPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'php_info';
    }

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
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => $this->getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'info',
            'text' => 'Php Info',
            'section' => 'system',
            'order' => 100,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws OutOfRangeException
     */
    protected function getTemplateData(): array
    {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();

        $phpinfo = "";
        if (preg_match("/.*?<body.*?>(.*?)<\/body>.*?/msi", $info, $matches)) {
            $phpinfo = $matches[1];
        }

        $style = "";
        if (preg_match("/.*?<style.*?>(.*?)<\/style>.*?/msi", $info, $matches)) {
            $style = $matches[1];
        }

        $style = preg_replace("/body {.*?}/msi","",$style);
        $style = implode("\n", array_map(function($line) {
            $line = trim ($line);
            if (str_starts_with($line, "table")) {
                $line = preg_replace("/width: .*?;/", "width: 100%;", $line);
            }
            if (str_starts_with($line, "td")) {
                $line = preg_replace("/font-size: .*?;/", "font-size: 100%;", $line);
            }
            return ".phpinfo ".$line;
        } , array_filter(explode("\n", $style))));

        $this->template_data = [
            'php_info' => '<div class="phpinfo">'.$phpinfo.'</div>',
            'style' => $style,
        ];

        return $this->template_data;
    }
}
