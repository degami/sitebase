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
use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

/**
 * "progress_manager_process" table migration
 */
class CreateProgressManagerProcessTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'progress_manager_process';

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
     * @throws EmptyException
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('pid', 'INT', null, ['UNSIGNED'])
            ->addColumn('callable', 'VARCHAR', [255])
            ->addColumn('total', 'INT', null, ['UNSIGNED'])
            ->addColumn('progress', 'INT', null, ['UNSIGNED'])
            ->addColumn('message', 'VARCHAR', [255])
            ->addColumn('started_at', 'TIMESTAMP', null, [])
            ->addColumn('ended_at', 'TIMESTAMP', null, [])
            ->addColumn('exit_status', 'INT', null, [])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
