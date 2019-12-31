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
 * "website" table migration
 */
class CreateWebsiteTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'website';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '00.1_'.parent::getName();
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
            ->addColumn('site_name', 'VARCHAR', [255], [], false, null)
            ->addColumn('domain', 'VARCHAR', [255], [], false, null)
            ->addColumn('aliases', 'VARCHAR', [1024], [], false, null)
            ->addColumn('default_locale', 'VARCHAR', [10])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addForeignKey('fk_website_language_locale', ['default_locale'], 'language', ['locale'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
