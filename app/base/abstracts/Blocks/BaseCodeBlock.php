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

namespace App\Base\Abstracts\Blocks;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Traits\BlockTrait;

/**
 * Base for code blocks
 */
abstract class BaseCodeBlock extends ContainerAwareObject
{
    use BlockTrait;

    /**
     * gets block html
     *
     * @param BasePage $current_page
     * @return string
     */
    abstract public function renderHTML(BasePage $current_page): string;

    /**
     * block content can be stored in cache
     * 
     * @return bool
     */
    public function isCachable() : bool
    {
        return true;
    }
}
