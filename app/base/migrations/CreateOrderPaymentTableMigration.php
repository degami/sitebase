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
use Degami\SqlSchema\ForeignKey;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateOrderPaymentTableMigration extends DBMigration
{
    protected string $tableName = 'order_payment';

    public function getName(): string
    {
        return '09.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {

        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('order_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('payment_method', 'VARCHAR', [255])
            ->addColumn('transaction_id', 'VARCHAR', [255])
            ->addColumn('transaction_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('currency_code', 'VARCHAR', [255])
            ->addColumn('additional_data', 'TEXT', null)
            ->addColumn('created_at', 'DATETIME', null, [], false)
            ->addColumn('updated_at', 'DATETIME', null, [], false)
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_orderpayment_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_orderpayment_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_orderpayment_order_id', ['order_id'], 'order', ['id'], ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
