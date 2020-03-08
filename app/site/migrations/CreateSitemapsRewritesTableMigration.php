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
 * "block_rewrite" table migration
 */
class CreateSitemapsRewritesTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'sitemap_rewrite';

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
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('sitemap_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('rewrite_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('priority', 'FLOAT', null, ['UNSIGNED'])
            ->addColumn('change_freq', 'VARCHAR', ['20'])
            ->addIndex('sitemap_rewrite', ['sitemap_id','rewrite_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_sitemap_rewrite_sitemap_id', ['sitemap_id'], 'sitemap', ['id'])
            ->addForeignKey('fk_sitemap_rewrite_rewrite_id', ['rewrite_id'], 'rewrite', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
