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

namespace App\Site\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

/**
 * "application_log" table migration
 */
class CreateApplicationLogTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'application_log';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '06_' . parent::getName();
    }

    /**
     * {@inheritdoc}
     *
     * @param Table $table
     * @return Table
     * @throws DuplicateException|EmptyException
     */
    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('ip_address', 'VARCHAR', [32])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], 'NULL')
            ->addColumn('file', 'VARCHAR', [1024])
            ->addColumn('line', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('level', 'VARCHAR', [10]) 
            ->addColumn('log_data', 'TEXT')
            ->addColumn('is_exception', 'BOOLEAN', null, []) 
            ->addColumn('exception_message', 'TEXT')
            ->addColumn('exception_trace', 'TEXT')
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_applicationlog_user_id', ['user_id'], 'user', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
