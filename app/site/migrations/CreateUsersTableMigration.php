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

use \App\Base\Abstracts\DBMigration;
use \Psr\Container\ContainerInterface;
use \Degami\SqlSchema\Index;
use \Degami\SqlSchema\Table;

/**
 * "user" table migration
 */
class CreateUsersTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'user';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '03_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param  Table $table
     * @return Table
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('username', 'VARCHAR', [50])
            ->addColumn('password', 'VARCHAR', [255])
            ->addColumn('role_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('email', 'VARCHAR', [255])
            ->addColumn('nickname', 'VARCHAR', [255])
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addIndex('username_unique', ['username'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_role_id', ['role_id'], 'role', ['id'])
            ->addForeignKey('fk_user_language_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
