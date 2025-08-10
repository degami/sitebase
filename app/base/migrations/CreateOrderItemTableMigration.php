<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\ForeignKey;

class CreateOrderItemTableMigration extends DBMigration
{
    protected string $tableName = 'order_item';

    public function getName(): string
    {
        return '09.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('order_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('cart_item_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('product_class', 'VARCHAR', [255])
            ->addColumn('product_id', 'INT', null, [], false)
            ->addColumn('quantity', 'INT', null, [], false)
            ->addColumn('unit_price', 'DECIMAL', ['12, 4'])
            ->addColumn('sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('currency_code', 'VARCHAR', [255])
            ->addColumn('admin_unit_price', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_currency_code', 'VARCHAR', [255])
            ->addColumn('created_at', 'DATETIME', null, [], false)
            ->addColumn('updated_at', 'DATETIME', null, [], false)
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_orderitem_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_orderitem_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_orderitem_order_id', ['order_id'], 'order', ['id'], ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
