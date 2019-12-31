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
 * "menu" table migration
 */
class CreateMenusTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'menu';

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
        $table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('parent_id', 'INT', null, ['UNSIGNED'], 'NULL')
            ->addColumn('title', 'VARCHAR', [255])
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('menu_name', 'VARCHAR', [64])
            ->addColumn('rewrite_id', 'INT', null, ['UNSIGNED'], 'NULL')
            ->addColumn('href', 'VARCHAR', [255], [], 'NULL')
            ->addColumn('target', 'VARCHAR', [255], [], 'NULL')
            ->addColumn('breadcrumb', 'VARCHAR', [1024])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addIndex('menu_name', 'menu_name', Index::TYPE_INDEX)
            ->addForeignKey('fk_menu_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_menu_rewrite_id', ['rewrite_id'], 'rewrite', ['id'])
            ->addForeignKey('fk_menu_language_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
