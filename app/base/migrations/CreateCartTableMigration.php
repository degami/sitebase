<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateCartTableMigration extends DBMigration
{
    protected string $tableName = 'cart';

    public function getName(): string
    {
        return '08_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('is_active', 'BOOLEAN', null, [], false, '1')
            ->addColumn('sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('shipping_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('currency_code', 'VARCHAR', [10])
            ->addColumn('admin_sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_shipping_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_currency_code', 'VARCHAR', [10])
            ->addColumn('shipping_address_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('billing_address_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_cart_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_cart_owner_id', ['user_id'], 'user', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
