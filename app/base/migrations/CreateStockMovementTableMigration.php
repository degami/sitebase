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

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

/**
 * "stock_movement" table migration
 */
class CreateStockMovementTableMigration extends DBMigration
{
    protected string $tableName = 'stock_movement';

    public function getName(): string
    {
        return '08.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('stock_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('movement_type', 'VARCHAR', [50])
            ->addColumn('quantity', 'INT', null, ['UNSIGNED'])
            ->addColumn('cart_item_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('order_item_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_stockmovement_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_stockmovement_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_stockmovement_stock_id', ['stock_id'], 'product_stock', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
