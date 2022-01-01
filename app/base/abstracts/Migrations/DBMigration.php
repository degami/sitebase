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

namespace App\Base\Abstracts\Migrations;

use Degami\Basics\Exceptions\BasicException;
use Exception;
use Degami\SqlSchema\Schema;
use Degami\SqlSchema\Table;

/**
 * Base for database migrations
 */
abstract class DBMigration extends BaseMigration
{
    /**
     * @var string table name
     */
    protected string $tableName;

    /**
     * {@inheritdocs}
     * @throws Exception
     */
    public function up() : void
    {
        $table = $sql = null;
        try {
            $schema = $this->getContainer()->make(Schema::class, ['pdo' => $this->getPdo(), 'preload' => true]);

            try {
                $table = $schema->getTable($this->tableName);
            } catch (Exception $e) {
                $table = $schema->addTable($this->tableName);
            }

            if ($table == null) {
                throw new Exception("Errors with table " . $this->tableName, 1);
            }

            $table = $this->addDBTableDefinition($table);

            $sql = $table->migrate();
            if ($this->getPdo()->exec($sql) === false) {
                throw new Exception("SQL Error: " . $this->getPdo()->errorInfo()[2], 1);
            }
        } catch (Exception $e) {
            if (isset($sql) && ($table instanceof Table)) {
                echo "SQL query:" . $sql;
            }
            throw $e;
        }
    }

    /**
     * {@inheritdocs}
     * @throws BasicException
     */
    public function down() : void
    {
        $this->getPdo()->exec("DROP TABLE {$this->tableName}");
    }

    /**
     * add db table definition
     *
     * @param Table $table
     * @return Table
     */
    abstract public function addDBTableDefinition(Table $table): Table;
}
