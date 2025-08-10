<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\ForeignKey;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateCartDiscountTableMigration extends DBMigration
{
    protected string $tableName = 'cart_discount';

    public function getName(): string
    {
        return '08.3_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('cart_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('cart_item_id', 'INT', null, ['UNSIGNED'], true)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('initial_discount_id', 'INT', null, ['UNSIGNED'], true)
            ->addColumn('discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('currency_code', 'VARCHAR', [10])
            ->addColumn('admin_discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_currency_code', 'VARCHAR', [10])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_cartdiscount_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_cartdiscount_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_cartdiscount_cart_id', ['cart_id'], 'cart', ['id'], ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE)
            ->addForeignKey('fk_cartdiscount_cart_item_id', ['cart_item_id'], 'cart_item', ['id'], ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE)
            ->addForeignKey('fk_cartdiscount_initial_discount_id', ['initial_discount_id'], 'discount', ['id'], ForeignKey::ACTION_SET_NULL, ForeignKey::ACTION_SET_NULL)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
