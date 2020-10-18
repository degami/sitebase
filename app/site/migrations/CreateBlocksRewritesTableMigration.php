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
 * "block_rewrite" table migration
 */
class CreateBlocksRewritesTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'block_rewrite';

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
     * @throws DuplicateException|EmptyException
     */
    public function addDBTableDefinition(Table $table)
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('block_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('rewrite_id', 'INT', null, ['UNSIGNED'])
            ->addIndex('block_rewrite', ['block_id', 'rewrite_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_block_rewrite_block_id', ['block_id'], 'block', ['id'])
            ->addForeignKey('fk_block_rewrite_rewrite_id', ['rewrite_id'], 'rewrite', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
