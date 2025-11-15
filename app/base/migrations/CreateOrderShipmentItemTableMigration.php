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
use Degami\SqlSchema\ForeignKey;

/**
 * "order_shipment_item" table migration
 */
class CreateOrderShipmentItemTableMigration extends DBMigration
{
    protected string $tableName = 'order_shipment_item';

    public function getName(): string
    {
        return '09.3_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('shipment_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('order_item_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('quantity', 'INT', null, [], false)
            ->addColumn('created_at', 'DATETIME', null, [], false)
            ->addColumn('updated_at', 'DATETIME', null, [], false)
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_shipmentitem_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_shipmentitem_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_shipmentitem_shipment_id', ['shipment_id'], 'order_shipment', ['id'], ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
