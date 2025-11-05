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

namespace App\Base\Cron\Tasks;

use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Models\StockMovement;

/**
 * Stock manager cron
 */
class StockManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '*/15 * * * *';

    /**
     * remove old (older than 30 minutes) stock reservations method
     *
     * @return bool
     * @throws PhpfastcacheSimpleCacheException|BasicException
     */
    public function removeOldReservations(): bool
    {
        StockMovement::getCollection()
            ->where([
                'movement_type' => StockMovement::MOVEMENT_TYPE_DECREASE,
                'updated_at < ?' => date('Y-m-d H:i:s', time() - StockMovement::MAX_CART_THRESHOLD * 60),
                'order_item_id' => null,
            ])
            ->delete();

        return true;
    }
}
