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

namespace App\Base\Tools\DataCollector;

use App\Base\Abstracts\Models\AccountModel;
use App\Site\Models\GuestUser;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;
use Redis as RedisClient;

/**
 * Redis data collector for debugging
 */
class RedisDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "Redis Data";

    /**
     * collects data
     *
     * @return array
     */
    public function collect(): array
    {
        
        try {
            $client = \App\App::getInstance()->getRedis();
        } catch (\Exception $e) {
            return [];
        }

        return [
            'host' => $client->getHost(),
            'port' => $client->getPort(),
            'database' => $client->getDBNum(),
            'dbsize' => $client->dbSize(),
            'keys' => json_encode($client->keys('*')),
        ];
    }

    /**
     * gets tab name
     *
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * gets tab widget
     *
     * @return array
     */
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                "icon" => "file-alt",
                "tooltip" => "Redis Data",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => self::NAME,
                "default" => "''"
            ]
        ];
    }

    /**
     * gets assets
     *
     * @return array
     */
    public function getAssets(): array
    {
        return [
            //            'css' => '',
            //            'js' => ''
        ];
    }
}
