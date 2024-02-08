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
        $client = new RedisClient();
        $isConnected = $client->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'), 5);
        if (!empty(getenv('REDIS_PASSWORD', ''))) {
            $client->auth(getenv('REDIS_PASSWORD',''));
        }
        $client->select(getenv('REDIS_DATABASE'));

        return [
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'database' => getenv('REDIS_DATABASE'),
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
