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

/**
 * Session data collector for debugging
 */
class UserDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "User Data";

    /**
     * PageDataCollector constructor.
     *
     * @param AccountModel|null $user
     */
    public function __construct(
        protected ?AccountModel $subject = null
    ) { }

    /**
     * collects data
     *
     * @return array
     */
    public function collect(): array
    {
        return [
            'user_id' => $this->subject?->getId(),
            'username' => $this->subject?->getUsername(),
            'since' => ($this->subject instanceof GuestUser) ? null : $this->subject?->getCreatedAt(),
            'role' => $this->subject?->getRole()?->getName(),
            'session' => $this->subject?->getUserSession()?->getSessionData(),
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
                "tooltip" => "User Data",
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
