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
 * "cart_item" table migration
 */
class CreateCartItemTableMigration extends DBMigration
{
    protected string $tableName = 'cart_item';

    public function getName(): string
    {
        return '08.1_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('cart_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('product_class', 'VARCHAR', [255])
            ->addColumn('product_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('quantity', 'INT', null, ['UNSIGNED'])
            ->addColumn('unit_price', 'DECIMAL', ['12, 4'])
            ->addColumn('sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('currency_code', 'VARCHAR', [10])
            ->addColumn('admin_unit_price', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_sub_total', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_tax_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_total_incl_tax', 'DECIMAL', ['12, 4'])
            ->addColumn('admin_currency_code', 'VARCHAR', [10])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_cartitem_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_cartitem_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_cartitem_cart_id', ['cart_id'], 'cart', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
