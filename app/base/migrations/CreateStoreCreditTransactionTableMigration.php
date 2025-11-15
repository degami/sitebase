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
use Psr\Container\ContainerInterface;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

/**
 * "store_credit_transaction" table migration
 */
class CreateStoreCreditTransactionTableMigration extends DBMigration
{
    protected string $tableName = 'store_credit_transaction';

    public function getName(): string
    {
        return '08.5_'.parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('store_credit_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('amount', 'FLOAT', null, [], true, null)
            ->addColumn('movement_type', 'VARCHAR', [50])
            ->addColumn('transaction_id', 'VARCHAR', [100])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_storecredittransaction_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_storecredittransaction_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_storecredittransaction_storecredit_id', ['store_credit_id'], 'store_credit', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
