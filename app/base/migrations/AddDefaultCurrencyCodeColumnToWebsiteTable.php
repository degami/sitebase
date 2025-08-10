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

namespace App\Base\Migrations;

use App\Base\Abstracts\Migrations\DBMigration;
use Degami\SqlSchema\Exceptions\DuplicateException;
use Degami\SqlSchema\Exceptions\EmptyException;
use Degami\SqlSchema\Table;

/**
 * add "default_currency_code" to website table migration
 */
class AddDefaultCurrencyCodeColumnToWebsiteTable extends DBMigration
{
    /**
     * @var string table name
     */
    protected string $tableName = 'website';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '09.1_' . parent::getName();
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
        $table->addColumn('default_currency_code', 'VARCHAR', [10], [], true, null);

        return $table;
    }
}
