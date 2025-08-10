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

namespace App\Base\Controllers\Admin;

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
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'php_info';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'system_info';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'info',
            'text' => 'Php Info',
            'section' => 'tools',
            'order' => 100,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws OutOfRangeException
     */
    public function getTemplateData(): array
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

        $style = preg_replace("/body {(.*?)}/msi","",$style);
        $style = implode("\n", array_map(function($line) {
            $line = trim ($line);
            if (str_starts_with($line, "table")) {
                $line = preg_replace("/width: .*?;/", "width: 100%;", $line);
            }
            if (str_starts_with($line, "td")) {
                $line = preg_replace("/font-size: .*?;/", "font-size: 100%;", $line);
            }
            if (str_starts_with($line, ".e ")) {
                $line = preg_replace("/}/", "; color: #fff;}", $line);
            }
            if (str_starts_with($line, ".h ")) {
                $line = preg_replace("/}/", "; color: #fff;}", $line);
            }
            if (str_starts_with($line, "th ")) {
                $line = preg_replace("/}/", "; color: #fff;}", $line);
            }
            if (str_starts_with($line, "h2 ")) {
                $line = preg_replace("/}/", "; font-weight: bolder;}", $line);
            }
            if (str_starts_with($line, "h1 ")) {
                $line = preg_replace("/}/", "; font-weight: bolder;}", $line);
            }
            return ".phpinfo ".$line;
        } , array_filter(explode("\n", $style))));

        $this->template_data = [
            'php_info' => '<div class="phpinfo">'.$phpinfo.'</div>',
            'style' => $style.':root {
  --php-dark-blue: #4F5D95;
}',
        ];

        return $this->template_data;
    }
}
