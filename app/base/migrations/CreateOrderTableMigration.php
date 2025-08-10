<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateOrderTableMigration extends DBMigration
{
    protected string $tableName = 'order';

    public function getName(): string
    {
        return '09.1_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {

        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('cart_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('order_number', 'VARCHAR', [255])
            ->addColumn('order_status_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('shipping_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('currency_code', 'VARCHAR', [255])
            ->addColumn('admin_discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_shipping_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_currency_code', 'VARCHAR', [255])
            ->addColumn('additional_data', 'TEXT', null)
            ->addColumn('created_at', 'DATETIME', null, [], false)
            ->addColumn('updated_at', 'DATETIME', null, [], false)
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_order_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_order_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_order_status_id', ['order_status_id'], 'order_status', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
