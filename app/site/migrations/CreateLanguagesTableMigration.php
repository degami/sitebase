<?php

/**
 * SiteBase
 * PHP Version 7.0
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
 * "language" table migration
 */
class CreateLanguagesTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'language';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName(): string
    {
        return '00.0_' . parent::getName();
    }

    /**
     * {@inheritdocs}
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
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('639-1', 'VARCHAR', [10])
            ->addColumn('639-2', 'VARCHAR', [10])
            ->addColumn('name', 'VARCHAR', [255])
            ->addColumn('native', 'VARCHAR', [255])
            ->addColumn('family', 'VARCHAR', [255])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addIndex('locale_unique', 'locale', Index::TYPE_UNIQUE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
