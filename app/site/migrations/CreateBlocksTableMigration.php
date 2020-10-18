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
 * "block" table migration
 */
class CreateBlocksTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'block';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '04_' . parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param Table $table
     * @return Table
     * @throws DuplicateException
     * @throws EmptyException
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('region', 'VARCHAR', [64])
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('instance_class', 'VARCHAR', [255])
            ->addColumn('title', 'VARCHAR', [255])
            ->addColumn('content', 'TEXT', null)
            ->addColumn('config', 'TEXT', null)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('order', 'INT', null, ['UNSIGNED'], false, '0')
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_block_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_block_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_block_language_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
