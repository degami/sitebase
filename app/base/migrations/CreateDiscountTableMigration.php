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

class CreateDiscountTableMigration extends DBMigration
{
    protected string $tableName = 'discount';

    public function getName(): string
    {
        return '08.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('title', 'VARCHAR', [255], [], false, '')
            ->addColumn('active', 'BOOLEAN', null, [], false, '1')
            ->addColumn('code', 'VARCHAR', [64])
            ->addColumn('discount_amount', 'DECIMAL', ['12, 4'])
            ->addColumn('discount_type', 'VARCHAR', [10])
            ->addColumn('max_usages', 'INT', null, [], true, null)
            ->addColumn('max_usages_per_user', 'INT', null, [], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_discount_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_discount_owner_id', ['user_id'], 'user', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
