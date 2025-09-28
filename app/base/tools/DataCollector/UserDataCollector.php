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

use App\Base\Abstracts\Models\AccountModel;
use App\Base\Models\GuestUser;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;

/**
 * Session data collector for debugging
 */
class UserDataCollector extends DataCollector implements Renderable, AssetProvider
{
    public const NAME = "User";

    /**
     * PageDataCollector constructor.
     *
     * @param AccountModel|null $subject
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
            'permissions' => '<ul><li>'. implode('</li><li>', array_map(fn ($permission) => $permission->name, $this->subject?->getRole()->getPermissionsArray())) . '</li><li>',
            'session' => $this->subject?->getUserSession()?->getSessionData(),
            'jwt' => $this->subject?->getJWT(),
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
