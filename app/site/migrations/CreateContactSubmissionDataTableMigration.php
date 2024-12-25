<?php

/**
 * SiteBase
 * PHP Version 8.0
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
 * "contact_submission_data" table migration
 */
class CreateContactSubmissionDataTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'contact_submission_data';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '07.3_' . parent::getName();
    }

    /**
     * {@inheritdoc}
     *
     * @param Table $table
     * @return Table
     * @throws EmptyException
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table): Table
    {
        $table
            ->addColumn('id', 'INT', null, ['UNSIGNED'], false)
            ->addColumn('contact_submission_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('contact_definition_id', 'INT', null, ['UNSIGNED'])
            ->addColumn('field_value', 'TEXT', null, [])
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->addForeignKey('fk_contact_submission_data_contact_submission_id', ['contact_submission_id'], 'contact_submission', ['id'])
            ->addForeignKey('fk_contact_submission_contact_definition_id', ['contact_definition_id'], 'contact_definition', ['id'])
            ->setAutoIncrementColumn('id');

        return $table;
    }
}
