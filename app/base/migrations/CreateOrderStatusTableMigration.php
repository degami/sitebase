<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateOrderStatusTableMigration extends DBMigration
{
    protected string $tableName = 'order_status';

    public function getName(): string
    {
        return '09_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {

        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('status', 'VARCHAR', [255])
            ->addColumn('created_at', 'DATETIME', null, [], false)
            ->addColumn('updated_at', 'DATETIME', null, [], false)
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_orderstatus_website_id', ['website_id'], 'website', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
