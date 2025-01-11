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
 * Page data collector for debugging
 */
class PageDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "Page Data";

    /**
     * PageDataCollector constructor.
     *
     * @param BasePage|null $page
     */
    public function __construct(
        protected ?BasePage $subject = null
    ) { }

    /**
     * collects data
     *
     * @return array
     */
    public function collect(): array
    {
        return $this->subject?->getInfo() ?? [];
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
                "tooltip" => "Page Variables",
                "widget" => "PhpDebugBar.Widgets.HtmlVariableListWidget",
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
