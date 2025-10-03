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
use RuntimeException;

/**
 * Page data collector for debugging
 */
class PageDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "Page";

    protected array $additionalInfo = [];

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
        return array_map(function ($el) {
            if (is_array($el)) {
                return '<ul><li>' . implode('</li><li>', $el) . '</li></ul>';
            }

            return $el;
        }, ($this->subject?->getInfo() ?? []) + $this->getAdditionalInfo());
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

    public function getAdditionalInfo() : array
    {
        return $this->additionalInfo;
    }

    public function addAdditionalInfo(string $key, mixed $value) : self
    {
        if (isset($this->additionalInfo[$key])) {
            throw new RuntimeException("key $key is already defined");
        }

        $this->additionalInfo[$key] = $value;

        return $this;
    }

    public function setAdditionalInfo(string|array $key, mixed $value = null) : self
    {
        if (is_array($key)) {
            $this->additionalInfo = $key;
        } else {
            $this->additionalInfo[$key] = $value;
        }

        return $this;
    }
}
