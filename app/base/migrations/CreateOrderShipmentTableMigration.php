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
 * "order_shipment" table migration
 */
class CreateOrderShipmentTableMigration extends DBMigration
{
    protected string $tableName = 'order_shipment';

    public function getName(): string
    {
        return '09.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {

        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('order_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('shipping_method', 'VARCHAR', [255])
            ->addColumn('shipment_code', 'VARCHAR', [255])
            ->addColumn('status', 'VARCHAR', [255])
            ->addColumn('additional_data', 'TEXT', null)
            ->addColumn('latitude', 'FLOAT', null, [], true, null)
            ->addColumn('longitude', 'FLOAT', null, [], true, null)
            ->addColumn('created_at', 'DATETIME', null, [], false)
            ->addColumn('updated_at', 'DATETIME', null, [], false)
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_shipment_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_shipment_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_shipment_order_id', ['order_id'], 'order', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
