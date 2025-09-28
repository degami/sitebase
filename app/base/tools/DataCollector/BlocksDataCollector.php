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
use App\Base\Abstracts\Controllers\BasePage;

/**
 * Blocks data collector for debugging
 */
class BlocksDataCollector extends DataCollector implements Renderable, AssetProvider
{

    public const NAME = "Blocks Data";

    protected $blocksInfo = [];

    /**
     * PageDataCollector constructor.
     *
     * @param BasePage|null $page
     */
    public function __construct() { }

    /**
     * collects data
     *
     * @return array
     */
    public function collect(): array
    {
        $out = ['n_blocks' => count($this->blocksInfo), 'blocks'];
        foreach ($this->blocksInfo as $region => $info) {
            $out['blocks'][$region] = implode(', ', array_map(fn($el) => trim(
                $el['className'] . 
                (!empty($el['params']) ? ':' . json_encode($el['params']) : '') . ' ' . 
                ($el['renderTime'] ?: '')
            ), $info));
        }
        return $out;
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
                "tooltip" => "Blocks Data",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => self::NAME.'.blocks',
                "default" => "[]",
            ],
            self::NAME.':badge' => [
                "map" => self::NAME.'.n_blocks',
                "default" => 0,
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

    public function addElements($region, $className, $params, $renderTime) : self 
    {
        $this->blocksInfo[$region][] = [
            'className' => $className,
            'params' => $params,
            'renderTime' => ($renderTime > 0) ? number_format($renderTime, 4) : 0,
        ];

        return $this;
    }
}
