<?php

namespace App\Site\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

class CreateBookTableMigration extends DBMigration
{
    protected string $tableName = 'book';

    public function getName(): string
    {
        return '178_'.parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('sku', 'VARCHAR', [255], [], true, null)
             ->addColumn('title', 'VARCHAR', [255], [], true, null)
             ->addColumn('content', 'TEXT', null, [], true, null)
             ->addColumn('tax_class_id', 'INT', null, ['UNSIGNED'], true, null)
             ->addColumn('website_id', 'INT', null, ['UNSIGNED'], true, null)
             ->addColumn('user_id', 'INT', null, ['UNSIGNED'], true, null)
             ->addColumn('price', 'FLOAT', null, [], true, null)
             ->addColumn('url', 'VARCHAR', [255], [], true, null)
             ->addColumn('locale', 'VARCHAR', [10], [], true, null)
             ->addColumn('meta_keywords', 'VARCHAR', [1024], [], true, null)
             ->addColumn('meta_description', 'VARCHAR', [1024], [], true, null)
             ->addColumn('html_title', 'VARCHAR', [1024], [], true, null)
             ->addColumn('weight', 'FLOAT', null, [], true, null)
             ->addColumn('length', 'FLOAT', null, [], true, null)
             ->addColumn('width', 'FLOAT', null, [], true, null)
             ->addColumn('height', 'FLOAT', null, [], true, null)
             ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_book_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_book_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_book_language_locale', ['locale'], 'language', ['locale'])
            ->addForeignKey('fk_book_tax_class_id', ['tax_class_id'], 'tax_class', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
