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
 * "user_download" table migration
 */
class CreateUserDownloadsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'user_download';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '04_' . parent::getName();
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
            ->addColumn('path', 'VARCHAR', [1024])
            ->addColumn('filename', 'VARCHAR', [255])
            ->addColumn('mimetype', 'VARCHAR', [50])
            ->addColumn('filesize', 'INT', null, ['UNSIGNED'])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('download_available_count', 'INT', null, [])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_user_download_owner_id', ['user_id'], 'user', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
