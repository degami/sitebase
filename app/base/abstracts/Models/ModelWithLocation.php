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

use App\Base\Traits\WithLatLngTrait;

/**
 * A model with location
 *
 * @method float getLatitude();
 * @method float getLongitude();
 * @method float distance(static $other);
 */
abstract class ModelWithLocation extends BaseModel
{
    use WithLatLngTrait;
}
