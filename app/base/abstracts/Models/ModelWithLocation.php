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

use App\App;
use App\Base\Traits\WithLatLngTrait;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithRewriteTrait;
use App\Base\Traits\IndexableTrait;
use App\Base\Traits\FrontendModelTrait;

/**
 * A model with location
 *
 * @method float getLatitude();
 * @method float getLongitude();
 * @method float distance(static $other);
 */
abstract class ModelWithLocation extends FrontendModel
{
    use WithLatLngTrait;
    use WithOwnerTrait;
    use WithWebsiteTrait;
    use WithRewriteTrait;
    use IndexableTrait;
    use FrontendModelTrait;

    public static function getCollection() : BaseCollection
    {
        $container = App::getInstance()->getContainer();
        return $container->make(ModelWithLocationCollection::class, ['className' => static::class]);
    }
}
