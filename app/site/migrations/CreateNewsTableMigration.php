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
 * "news" table migration
 */
class CreateNewsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'news';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '04_' . parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param Table $table
     * @return Table
     * @throws EmptyException
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('url', 'VARCHAR', [64])
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('title', 'VARCHAR', [255], [], false, null)
            ->addColumn('content', 'TEXT', null, [], true, null)
            ->addColumn('date', 'DATETIME', null, [], true, null)
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_news_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_news_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_news_language_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
