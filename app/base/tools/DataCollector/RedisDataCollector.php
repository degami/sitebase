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

namespace App\Base\Tools\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;
use App\Base\Tools\Redis\Manager as RedisManager;

/**
 * Redis data collector for debugging
 */
class RedisDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "Redis";

    /**
     * PageDataCollector constructor.
     *
     * @param BasePage|null $page
     */
    public function __construct(
        protected ?RedisManager $subject = null
    ) { }


    /**
     * collects data
     *
     * @return array
     */
    public function collect(): array
    {
        if (!$this->subject) {
            return [];
        }

        return [
            'info' => [
                'host' => $this->subject->getHost(),
                'port' => $this->subject->getPort(),
                'database' => $this->subject->getDBNum(),
                'dbsize' => $this->subject->dbSize(),
                'keys' => '<ul><li>'.implode('</li><li>', $this->subject->keys('*')).'</li></ul>',
            ],
            'n_keys' => count($this->subject->keys('*'))
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
                "tooltip" => self::NAME,
                "widget" => "PhpDebugBar.Widgets.HtmlVariableListWidget",
                "map" => self::NAME.'.info',
                "default" => "[]"
            ],
            self::NAME.':badge' => [
                "map" => self::NAME.'.n_keys',
                "default" => 0,
            ],
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
