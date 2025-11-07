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
 * "book_media_element" table migration
 */
class CreateBooksMediaElementsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'book_media_element';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '10.1_' . parent::getName();
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
            ->addColumn('book_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('media_element_id', 'INT', null, ['UNSIGNED'])
            ->addIndex('book_media_element', ['book_id', 'media_element_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_book_media_element_book_id', ['book_id'], 'book', ['id'])
            ->addForeignKey('fk_book_media_element_media_element_id', ['media_element_id'], 'media_element', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
