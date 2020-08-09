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
 * "contact" table migration
 */
class CreateContactTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'contact';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '07.0_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param Table $table
     * @return Table
     * @throws EmptyException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('url', 'VARCHAR', [64])
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('title', 'VARCHAR', [255])
            ->addColumn('meta_keywords', 'VARCHAR', [1024])
            ->addColumn('meta_description', 'VARCHAR', [1024])
            ->addColumn('html_title', 'VARCHAR', [255])
            ->addColumn('content', 'TEXT', null)
            ->addColumn('template_name', 'VARCHAR', [1024])
            ->addColumn('submit_to', 'VARCHAR', [255])
            ->addColumn('user_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_contact_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_contact_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_contact_language_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
