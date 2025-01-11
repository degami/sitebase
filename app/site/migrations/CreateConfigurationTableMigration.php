<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use Degami\SqlSchema\Index;
use Degami\SqlSchema\Table;

/**
 * "configuration" table migration
 */
class CreateConfigurationTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'configuration';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '01_' . parent::getName();
    }

    /**
     * {@inheritdoc}
     *
     * @param Table $table
     * @return Table
     * @throws DuplicateException
     * @throws EmptyException
     */
    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('website_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('locale', 'VARCHAR', [10])
            ->addColumn('path', 'VARCHAR', [64])
            ->addColumn('value', 'VARCHAR', [255])
            ->addColumn('is_system', 'BOOLEAN', null)
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addIndex('path_unique', ['website_id', 'locale', 'path'], Index::TYPE_UNIQUE)
            ->addForeignKey('fk_configuration_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_page_configuration_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');


        return $table;
    }
}
