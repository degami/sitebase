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

namespace App\Base\Abstracts\Models;

use App\Base\Traits\WithChildrenTrait;

/**
 * A model that will be shown on frontend with children
 */
abstract class FrontendModelWithChildren extends FrontendModel
{
    use WithChildrenTrait;
}
