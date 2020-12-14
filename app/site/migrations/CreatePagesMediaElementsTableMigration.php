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

use \App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use \Degami\SqlSchema\Index;
use \Degami\SqlSchema\Table;

/**
 * "page_media_element" table migration
 */
class CreatePagesMediaElementsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'page_media_element';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName(): string
    {
        return '05_' . parent::getName();
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
            ->addColumn('page_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('media_element_id', 'INT', null, ['UNSIGNED'])
            ->addIndex('page_media_element', ['page_id', 'media_element_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_page_media_element_page_id', ['page_id'], 'page', ['id'])
            ->addForeignKey('fk_page_media_element_media_element_id', ['media_element_id'], 'media_element', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
