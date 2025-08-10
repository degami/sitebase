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

class CreateAddressTableMigration extends DBMigration
{
    protected string $tableName = 'address';

    public function getName(): string
    {
        return '08.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('first_name', 'VARCHAR', [100])
            ->addColumn('last_name', 'VARCHAR', [100])
            ->addColumn('company', 'VARCHAR', [255])
            ->addColumn('address1', 'VARCHAR', [255])
            ->addColumn('address2', 'VARCHAR', [255])
            ->addColumn('city', 'VARCHAR', [100])
            ->addColumn('state', 'VARCHAR', [100])
            ->addColumn('postcode', 'VARCHAR', [20])
            ->addColumn('country_code', 'VARCHAR', [5])
            ->addColumn('phone', 'VARCHAR', [30])
            ->addColumn('email', 'VARCHAR', [255])
            ->addColumn('latitude', 'FLOAT', null, [], true, null)
            ->addColumn('longitude', 'FLOAT', null, [], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_address_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_address_owner_id', ['user_id'], 'user', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
