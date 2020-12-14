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
 * "admin_action_log" table migration
 */
class CreateAdminActionLogTableMigration extends DBMigration
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
    public function getName(): string
    {
        return '06_' . parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param Table $table
     * @return Table
     * @throws DuplicateException|EmptyException
     */
    public function addDBTableDefinition(Table $table): Table
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('url', 'VARCHAR', [1024])
            ->addColumn('method', 'VARCHAR', [10])
            ->addColumn('ip_address', 'VARCHAR', [32])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'], 'NULL')
            ->addColumn('action', 'VARCHAR', [1024])
            ->addColumn('route_info', 'TEXT')
            ->addColumn('log_data', 'TEXT')
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_adminactionlog_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_adminactionlog_user_id', ['user_id'], 'user', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
