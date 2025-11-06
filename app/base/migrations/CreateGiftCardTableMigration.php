<?php

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Psr\Container\ContainerInterface;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

class CreateGiftCardTableMigration extends DBMigration
{
    protected string $tableName = 'gift_card';

    public function getName(): string
    {
        return '8.5_'.parent::getName();
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
             ->addColumn('credit', 'FLOAT', null, ['UNSIGNED'], true, null)
             ->addColumn('media_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_gift_card_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_gift_card_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_gift_card_language_locale', ['locale'], 'language', ['locale'])
            ->addForeignKey('fk_gift_card_tax_class_id', ['tax_class_id'], 'tax_class', ['id'])
            ->addForeignKey('fk_gift_card_media_id', ['media_id'], 'media_element', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
