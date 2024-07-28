<?php

/**
 * SiteBase
 * PHP Version 8.0
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
 * "user" table migration
 */
class AddUserLockingMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'user';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName(): string
    {
        return '04_' . parent::getName();
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
        $table->addColumn('locked', 'BOOLEAN', null, [], false, false)
            ->addColumn('login_tries', 'INT', null, ['UNSIGNED'], true, null)
            ->addColumn('locked_since', 'TIMESTAMP', null, [], true, null)
            ->addColumn('locked_until', 'TIMESTAMP', null, [], true, null);

        return $table;
    }
}
