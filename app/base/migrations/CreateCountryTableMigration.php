<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Table;
use Degami\SqlSchema\Index;

class CreateCountryTableMigration extends DBMigration
{
    protected string $tableName = 'country';

    public function getName(): string
    {
        return '08_' . parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('iso2', 'VARCHAR', [2])
            ->addColumn('iso3', 'VARCHAR', [3])
            ->addColumn('name_en', 'VARCHAR', [255])
            ->addColumn('name_native', 'VARCHAR', [255])
            ->addColumn('capital', 'VARCHAR', [255])
            ->addColumn('latitude', 'FLOAT', null, [], true, null)
            ->addColumn('longitude', 'FLOAT', null, [], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
