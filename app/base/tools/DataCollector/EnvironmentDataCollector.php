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
use App\Base\Environment\Manager as EnvironmentManager;

/**
 * Environment data collector for debugging
 */
class EnvironmentDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "Environment Data";

    /**
     * PageDataCollector constructor.
     *
     * @param EnvironmentManager|null $subject
     */
    public function __construct(
        protected ?EnvironmentManager $subject = null
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
                'flags' => [
                    'isCli' => $this->subject->isCli() ?: 'false',
                    'isCliServer' => $this->subject->isCliServer() ?: 'false',
                    'isWeb' => $this->subject->isWeb() ?: 'false',
                    'isDocker' => $this->subject->isDocker() ?: 'false',
                ],
                'variables' => $this->subject->getVariables(),                
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
                "tooltip" => "Environment Data",
                "widget" => "PhpDebugBar.Widgets.EnvironmentWidget",
                "map" => self::NAME.'.info',
                "default" => "[]"
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
            // 'css' => '',
            // 'js' => ['/js/debugbar-EnvironmentWidget.js'],
        ];
    }
}
