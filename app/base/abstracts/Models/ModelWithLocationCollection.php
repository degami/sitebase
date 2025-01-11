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

/**
 * A LessQL Collection
 * @package App\Base\Abstracts\Models
 */
class ModelWithLocationCollection extends BaseCollection
{
    protected int $EARTHRADIUS = 6371000;

    /**
     * get a list of elements inside a radius from a starting point
     * 
     * @param float $latitude latitude of starting point
     * @param float $longitude longitude of starting point
     * @param float $radius radius in meters
     * @return self
     */
    public function withinRange(float $latitude, float $longitude, float $radius) : self
    {
        return $this
            ->addSelect('*')
            ->addSelect("( " . $this->EARTHRADIUS . " * acos( cos( radians(".$latitude.") ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(".$longitude.") ) + sin( radians(".$latitude.") ) * sin( radians( latitude ) ) ) ) AS distance")
            ->addHaving('distance < '. $radius);
    }
}