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
 * "mail_log" table migration
 */
class CreateMailLogTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'mail_log';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '05_'.parent::getName();
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
            ->addColumn('from', 'VARCHAR', [1024])
            ->addColumn('to', 'VARCHAR', [1024])
            ->addColumn('subject', 'VARCHAR', [1024])
            ->addColumn('template_name', 'VARCHAR', [1024])
            ->addColumn('result', 'BOOLEAN', null, [])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
