<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateTaxClassTableMigration extends DBMigration
{
    protected string $tableName = 'tax_class';

    public function getName(): string
    {
        return '08.2_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('class_name', 'VARCHAR', [100])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_tax_class_website_id', ['website_id'], 'website', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
