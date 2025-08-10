<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateTaxRateTableMigration extends DBMigration
{
    protected string $tableName = 'tax_rate';

    public function getName(): string
    {
        return '08.3_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('tax_class_id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('country_code', 'VARCHAR', [100])
            ->addColumn('rate', 'FLOAT', null, [], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_tax_rate_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_tax_rate_tax_class_id', ['tax_class_id'], 'tax_class', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
