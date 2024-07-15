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

namespace App\Base\Traits;

/**
 * Trait for elements that can be indexed
 */
trait IndexableTrait
{
    /**
     * Field names to be exposed to indexer
     *
     * @return string[]
     */
    public static function exposeToIndexer(): array
    {
        return ['title', 'content'];
    }
}