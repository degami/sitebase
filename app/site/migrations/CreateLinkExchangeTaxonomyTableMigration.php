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
 * "link_exchange_taxonomy" table migration
 */
class CreateLinkExchangeTaxonomyTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'link_exchange_taxonomy';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '05_' . parent::getName();
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
            ->addColumn('link_exchange_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('taxonomy_id', 'INT', null, ['UNSIGNED'])
            ->addIndex('link_exchange_taxonomy', ['link_exchange_id', 'taxonomy_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_link_exchange_taxonomy_page_id', ['link_exchange_id'], 'link_exchange', ['id'])
            ->addForeignKey('fk_link_exchange_taxonomy_taxonomy_id', ['taxonomy_id'], 'taxonomy', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
