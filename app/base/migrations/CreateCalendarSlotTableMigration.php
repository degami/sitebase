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
use Psr\Container\ContainerInterface;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

/**
 * "calendar_slot" table migration
 */
class CreateCalendarSlotTableMigration extends DBMigration
{
    protected string $tableName = 'calendar_slot';

    public function getName(): string
    {
        return '09.1_'.parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('calendar_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('start', 'DATETIME', null, [], true, null)
            ->addColumn('end', 'DATETIME', null, [], true, null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_calendar_slot_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_calendar_slot_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_calendar_slot_calendar_id', ['calendar_id'], 'calendar', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
