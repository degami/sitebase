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

namespace App\Base\AI\Flows;

abstract class BaseFlow
{
    abstract public function systemPrompt() : string;

    abstract public function tools() : array;

    abstract public function toolHandlers() : array;

    public function schema(): string
    {
        return '';
    }
}
