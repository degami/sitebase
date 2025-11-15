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

namespace App\Base\Models;

use App\Base\Abstracts\Models\BaseModel;


/**
 * Order Shipment History Model
 * 
 * @method int getShipmentId()
 * @method float getLatitude()
 * @method float getLongitude()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setShipmentId(int $shipment_id)
 * @method self setLatitude(float $latitude)
 * @method self setLongitude(float $longitude)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
class OrderShipmentHistory extends BaseModel
{
}
