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
namespace App\Base\Abstracts;

use \Exception;
use \Degami\SqlSchema\Table;

/**
 * Base for database migrations
 */
abstract class DBMigration extends Migration
{
    /** @var string table name */
    protected $tableName;

    /**
     * {@inheritdocs}
     */
    public function up()
    {
        try {
            $schema = $this->getSchema();
            $table = $schema->addTable($this->tableName);
            $table = $this->addDBTableDefinition($table);
            if ($this->getPdo()->exec($table->showCreate()) === false) {
                throw new Exception("SQL Error: ".$this->getPdo()->errorInfo()[2], 1);
            }
        } catch (Exception $e) {
            if ($table instanceof Table) {
                echo $table->showCreate();
            }
            throw $e;
        }
    }

    /**
     * {@inheritdocs}
     */
    public function down()
    {
        $this->getPdo()->exec("DROP TABLE {$this->tableName}");
    }

    /**
     * add db table definition
     * @param Table $table
     * @return Table
     */
    abstract public function addDBTableDefinition(Table $table);
}
