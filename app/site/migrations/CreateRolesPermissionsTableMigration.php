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
 * "role_permission" table migration
 */
class CreateRolesPermissionsTableMigration extends DBMigration
{
    /** @var string table name */
    protected $tableName = 'role_permission';

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getName()
    {
        return '02_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     * @param Table $table
     * @return Table
     */
    public function addDBTableDefinition(Table $table)
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('role_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('permission_id', 'INT', null, ['UNSIGNED'])
            ->addIndex('role_permission', ['role_id','permission_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_role_permission_role_id', ['role_id'], 'role', ['id'])
            ->addForeignKey('fk_role_permission_permission_id', ['permission_id'], 'permission', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
