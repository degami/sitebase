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

namespace App\Base\Interfaces\Commerce;

use App\Base\Models\OrderShipment;

interface ShipmentTrackerInterface
{
    public function supports(string $carrier): bool;

    public function fetchTrackingData(OrderShipment $shipment): array;

    public function updateShipmentStatus(OrderShipment $shipment): void;
}
