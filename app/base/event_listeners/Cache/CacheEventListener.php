<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\EventListeners\Cache;

use App\App;
use App\Base\Interfaces\EventListenerInterface;
use Degami\EventManager\Event;

class CacheEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'commit_html_blocks' => [$this, 'commitHtmlBlocks']
        ];
	}

    public function commitHtmlBlocks(Event $e) 
    {

        $htmlPartsRenderer = App::getInstance()->getHtmlRenderer();

        $cachableBlocks = $htmlPartsRenderer->getUncommittedCachableBlocks();
        foreach ($cachableBlocks as $cache_key => $out) {
            App::getInstance()->getCache()->set($cache_key, $out);
        }

        $htmlPartsRenderer->resetUncommittedCachableBlocks();
    }
}