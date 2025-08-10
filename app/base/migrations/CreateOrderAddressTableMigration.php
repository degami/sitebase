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

class CreateOrderAddressTableMigration extends DBMigration
{
    protected string $tableName = 'order_address';

    public function getName(): string
    {
        return '09.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('order_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('type', 'VARCHAR', [255])
            ->addColumn('first_name', 'VARCHAR', [255])
            ->addColumn('last_name', 'VARCHAR', [255])
            ->addColumn('company', 'VARCHAR', [255])
            ->addColumn('address1', 'VARCHAR', [255])
            ->addColumn('address2', 'VARCHAR', [255])
            ->addColumn('city', 'VARCHAR', [255])
            ->addColumn('state', 'VARCHAR', [255])
            ->addColumn('postcode', 'VARCHAR', [255])
            ->addColumn('country_code', 'VARCHAR', [255])
            ->addColumn('phone', 'VARCHAR', [255])
            ->addColumn('email', 'VARCHAR', [255])
            ->addColumn('latitude', 'FLOAT', null, [], true, null)
            ->addColumn('longitude', 'FLOAT', null, [], true, null)
            ->addColumn('created_at', 'DATETIME', null, [], false)
            ->addColumn('updated_at', 'DATETIME', null, [], false)
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_orderaddress_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_orderaddress_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_orderaddress_order_id', ['order_id'], 'order', ['id'], ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
