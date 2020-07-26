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
use \Psr\Container\ContainerInterface;
use \Degami\SqlSchema\Index;
use \Degami\SqlSchema\Table;

/**
 * "add user_agent column" to admin_action_log table migration
 */
class AddNavigatorToAdminActionLogTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'admin_action_log';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '07_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param  Table $table
     * @return Table
     */
    public function addDBTableDefinition(Table $table)
    {
        $table
            ->addColumn('user_agent', 'VARCHAR', [1024]);

        return $table;
    }
}