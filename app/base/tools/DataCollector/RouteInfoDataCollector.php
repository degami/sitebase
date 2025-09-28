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

use App\Base\Routing\RouteInfo;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;
use App\Base\Tools\Redis\Manager as RedisManager;
use FastRoute\Dispatcher;

/**
 * RouteInfo data collector for debugging
 */
class RouteInfoDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "RouteInfo";

    /**
     * PageDataCollector constructor.
     *
     * @param RouteInfo|null $subject
     */
    public function __construct(
        protected ?RouteInfo $subject = null
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
                'status' => match($this->subject->getStatus()) {
                    Dispatcher::FOUND => 'FOUND',
                    Dispatcher::NOT_FOUND => 'NOT_FOUND',
                    Dispatcher::METHOD_NOT_ALLOWED => 'METHOD_NOT_ALLOWED',
                },
                'handler' => json_encode($this->subject->getHandler()),
                'allowed_methods' => json_encode($this->subject->getAllowedMethods()),
                'vars' => json_encode($this->subject->getVars()),
                'uri' => $this->subject->getUri(),
                'http_method' => $this->subject->getHttpMethod(),
                'route' => $this->subject->getRoute(),
                'route_name' => $this->subject->getRouteName(),
                'rewrite' => $this->subject->getRewrite(),
                'type' => $this->subject->getType(),
            ],
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
