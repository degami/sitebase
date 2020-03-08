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
 * "sitemap" table migration
 */
class CreateSitemapsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'sitemap';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '04_'.parent::getName();
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
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('title', 'VARCHAR', [255])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('published_on', 'TIMESTAMP', null, [], true, 'NULL')
            ->addColumn('content', 'TEXT', null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_sitemap_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_sitemap_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_sitemap_language_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
