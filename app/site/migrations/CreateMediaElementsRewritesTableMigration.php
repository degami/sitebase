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
 * "media_element_rewrite" table migration
 */
class CreateMediaElementsRewritesTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'media_element_rewrite';

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
            ->addColumn('id', 'INT', null, ['UNSIGNED'])
            ->addColumn('media_element_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('rewrite_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'])
            ->addIndex('media_element_rewrite', ['media_element_id','rewrite_id'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_media_element_rewrite_media_element_id', ['media_element_id'], 'media_element', ['id'])
            ->addForeignKey('fk_media_element_rewrite_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_media_element_rewrite_rewrite_id', ['rewrite_id'], 'rewrite', ['id'])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
