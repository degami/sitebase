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
 * "rewrite_translation" table migration
 */
class CreateRewriteTranslationsTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'rewrite_translation';

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
     * @param Table $table
     * @return Table
     * @throws EmptyException
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table)
    {
        $table->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('source', 'INT', null, ['UNSIGNED'])
            ->addColumn('source_locale', 'VARCHAR', [10])
            ->addColumn('destination', 'INT', null, ['UNSIGNED'])
            ->addColumn('destination_locale', 'VARCHAR', [10])
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_rewrite_translation_source_language_locale', ['source_locale'], 'language', ['locale'])
            ->addForeignKey('fk_rewrite_translation_destination_language_locale', ['destination_locale'], 'language', ['locale'])
            ->addForeignKey('fk_rewrite_source_id', ['source'], 'rewrite', ['id'])
            ->addForeignKey('fk_rewrite_destination_id', ['destination'], 'rewrite', ['id'])
            ->addIndex('rewrite_translation_unique', ['source','destination','destination_locale'], Index::TYPE_UNIQUE)
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
