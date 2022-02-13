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

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\ModelWithChildren;

/**
 * Trait for elements with latitude and longitude
 */
trait WithLatLngTrait
{
    public const EARTHRADIUS = 6371000;

    /**
     * @var float latitude
     */
    protected float $latitude = 0.0;

    /**
     * @var float longitude
     */
    protected float $longitude = 0.0;

    /**
     * gets latitude
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * gets longitude
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * calculate distance from other WithLatLngTrait object
     *
     * @return float
     */
    public function distance(static $other): float
    {
      // convert from degrees to radians
      $latFrom = deg2rad($this->getLatitude());
      $lonFrom = deg2rad($this->getLongitude());
      $latTo = deg2rad($other->getLatitude());
      $lonTo = deg2rad($other->getLongitude());

      $latDelta = $latTo - $latFrom;
      $lonDelta = $lonTo - $lonFrom;

      $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
      return $angle * static::EARTHRADIUS;
    }
}
