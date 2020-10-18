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
 * "page_taxonomy" table migration
 */
class CreatePagesTaxonomyTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'page_taxonomy';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
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
    public function addDBTableDefinition(Table $table)
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('page_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('taxonomy_id', 'INT', null, ['UNSIGNED'])
            ->addIndex('page_taxonomy', ['page_id', 'taxonomy_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_page_taxonomy_page_id', ['page_id'], 'page', ['id'])
            ->addForeignKey('fk_page_taxonomy_taxonomy_id', ['taxonomy_id'], 'taxonomy', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
