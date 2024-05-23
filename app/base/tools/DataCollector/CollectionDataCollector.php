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

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;
use App\Base\Abstracts\Controllers\BasePage;

/**
 * Page data collector for debugging
 */
class CollectionDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "Collections Data";

    protected $collectionsInfo = [];

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
        $out = ['n_collections' => count($this->collectionsInfo), 'collections'];
        foreach ($this->collectionsInfo as $className => $info) {
            $out['collections'][basename(str_replace("\\","/",$className))."Collection"] = count($info['keys']). ' items, '.$this->convert($info['size']);
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
                "tooltip" => "Collections Data",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => self::NAME.'.collections',
                "default" => "[]",
            ],
            self::NAME.':badge' => [
                "map" => self::NAME.'.n_collections',
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

    public function addElements($className, $keys, $allocatedSize) : self 
    {
        if (!isset($this->collectionsInfo[$className])) {
            $this->collectionsInfo[$className] = ['keys' => [], 'size' => 0];
        }

        $this->collectionsInfo[$className]['keys'] = array_unique(array_merge($this->collectionsInfo[$className]['keys'], $keys));
        $this->collectionsInfo[$className]['size'] += $allocatedSize;

        return $this;
    }

    protected function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}
