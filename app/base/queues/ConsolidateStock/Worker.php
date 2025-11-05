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

namespace App\Base\Queues\ConsolidateStock;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Queues\BaseQueueWorker;
use App\Base\Models\Order;
use App\Base\Interfaces\Model\PhysicalProductInterface;
use App\Base\Models\OrderItem;
use App\Base\Models\ProductStock;

/**
 * ConsolidateStock Queue Worker
 */
class Worker extends BaseQueueWorker
{
    /**
     * {@inheritdoc}
     *
     * @param array $message_data
     * @return bool
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function processMessage(array $message_data): bool
    {
        if (isset($message_data['order_id'])) {
            $order = Order::load($message_data['order_id']);
            foreach ($order->getItems() as $item) {
                /** @var OrderItem $item */
                $product = $item->getProduct();

                /** @var PhysicalProductInterface $product */
                if ($product instanceof PhysicalProductInterface) {
                    /** @var ProductStock $stockItem */
                    $stockItem = call_user_func([$product, 'getProductStock']);
                    if ($stockItem) {
                        $stockItem->consolidateStock();
                    }
                }
            }

            return true;
        } else if (isset($message_data['product_stock_id'])) {
            $stockItem = ProductStock::load($message_data['product_stock_id']);
            $stockItem->consolidateStock();

            return true;
        }

        return false;
    }
}
