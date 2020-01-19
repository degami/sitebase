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
 * "queue_message" table migration
 */
class CreateQueueMessageTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'queue_message';

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
     * @param  Table $table
     * @return Table
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('queue_name', 'VARCHAR', [255])
            ->addColumn('message', 'TEXT', null)
            ->addColumn('status', 'VARCHAR', [255])
            ->addColumn('result', 'INT', null, ['UNSIGNED'])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_queue_message_website_id', ['website_id'], 'website', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
