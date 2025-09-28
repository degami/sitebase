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

use App\App;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;
use App\Base\Tools\Cache\Manager as CacheManager;

/**
 * Cache data collector for debugging
 */
class CacheDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "Cache";

    /**
     * PageDataCollector constructor.
     *
     * @param CacheManager|null $subject
     */
    public function __construct(
        protected ?CacheManager $subject = null
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
                'Info' => $this->subject->getStats()->getInfo(),
                'Cache Size' => App::getInstance()->getUtils()->formatBytes($this->subject->getStats()->getSize()),
                'Cache LifeTime' => $this->subject->getCacheLifetime(),
                'keys' => '<ul><li>'.implode('</li><li>', $this->subject->keys()).'</li></ul>',
            ],
            'n_keys' => count($this->subject->keys())
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
