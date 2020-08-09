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
 * "permission" table migration
 */
class CreatePermissionsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'permission';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '01_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param Table $table
     * @return Table
     * @throws EmptyException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('name', 'VARCHAR', [50])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addIndex('name_unique', 'name', Index::TYPE_UNIQUE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
