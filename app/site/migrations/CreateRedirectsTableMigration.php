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
 * "redirect" table migration
 */
class CreateRedirectsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'redirect';

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
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('url_from', 'VARCHAR', [255])
            ->addColumn('url_to', 'VARCHAR', [255])
            ->addColumn('redirect_code', 'VARCHAR', [10])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_redirect_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_redirect_owner_id', ['user_id'], 'user', ['id'])
            ->addIndex('url_from_unique', ['website_id', 'url_from'], Index::TYPE_UNIQUE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
