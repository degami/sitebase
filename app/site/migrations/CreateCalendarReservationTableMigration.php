<?php

namespace App\Site\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Psr\Container\ContainerInterface;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

class CreateCalendarReservationTableMigration extends DBMigration
{
    protected string $tableName = 'calendar_reservation';

    public function getName(): string
    {
        return '10_'.parent::getName();
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
            ->addColumn('calendar_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('duration', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_calendar_reservation_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_calendar_reservation_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_calendar_reservation_language_locale', ['locale'], 'language', ['locale'])
            ->addForeignKey('fk_calendar_reservation_tax_class_id', ['tax_class_id'], 'tax_class', ['id'])
            ->addForeignKey('fk_calendar_reservation_calendar_id', ['calendar_id'], 'calendar', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
