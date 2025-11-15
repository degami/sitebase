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
 * "calendar" table migration
 */
class CreateCalendarTableMigration extends DBMigration
{
    protected string $tableName = 'calendar';

    public function getName(): string
    {
        return '09_'.parent::getName();
    }

    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('title', 'VARCHAR', [255])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_calendar_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_calendar_owner_id', ['user_id'], 'user', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
