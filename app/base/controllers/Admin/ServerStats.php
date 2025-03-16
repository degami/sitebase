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
 * "ServerStats" Admin Page
 */
class ServerStats extends AdminPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'server_stats';
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
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'monitor',
            'text' => 'Server Stats',
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
        $this->page_title = 'Server Status';
        
        $memoryInfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+) kB/', $memoryInfo, $totalMemoryMatches);
        preg_match('/MemAvailable:\s+(\d+) kB/', $memoryInfo, $availableMemoryMatches);

        $memoryTotal = $totalMemoryMatches[1] * 1024; // in bytes
        $memoryFree = $availableMemoryMatches[1] * 1024; // in bytes
        $memoryUsed = $memoryTotal - $memoryFree;

        $cpuLoad = sys_getloadavg()[0]; // Carico medio a 1 minuto

        $diskTotal = disk_total_space("/"); // in bytes
        $diskFree = disk_free_space("/"); // in bytes
        $diskUsed = $diskTotal - $diskFree;

        $this->template_data = [
            'memoryTotal' => $this->formatBytes($memoryTotal),
            'memoryUsed' => $this->formatBytes($memoryUsed),
            'memoryFree' => $this->formatBytes($memoryFree),
            'cpuLoad' => round($cpuLoad, 2) . '%',
            'diskTotal' => $this->formatBytes($diskTotal),
            'diskUsed' => $this->formatBytes($diskUsed),
            'diskFree' => $this->formatBytes($diskFree),
        ];

        return $this->template_data;
    }
}
