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
 * "contact_definition" table migration
 */
class CreateContactDefinitionTableMigration extends DBMigration
{
    /** @var string table name */
    protected $tableName = 'contact_definition';

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getName()
    {
        return '07.1_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     * @param Table $table
     * @return Table
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('contact_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('field_type', 'VARCHAR', [255])
            ->addColumn('field_label', 'VARCHAR', [255])
            ->addColumn('field_required', 'BOOLEAN', null, [])
            ->addColumn('field_data', 'VARCHAR', [1024])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_contact_definition_contact_id', ['contact_id'], 'contact', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
