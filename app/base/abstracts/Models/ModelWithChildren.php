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

namespace App\Base\Abstracts\Models;

use App\Base\Traits\WithChildrenTrait;

/**
 * A model with children
 *
 * @method int getPosition();
 * @method self setPosition(int $position);
 */
abstract class ModelWithChildren extends BaseModel
{
    use WithChildrenTrait;
}
