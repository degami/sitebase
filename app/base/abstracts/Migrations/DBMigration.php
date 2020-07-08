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
namespace App\Base\Abstracts\Migrations;

use \Exception;
use \Degami\SqlSchema\Schema;
use \Degami\SqlSchema\Table;

/**
 * Base for database migrations
 */
abstract class DBMigration extends BaseMigration
{
    /**
     * @var string table name
     */
    protected $tableName;

    /**
     * {@inheritdocs}
     */
    public function up()
    {
        try {
            $schema = $this->getContainer()->make(Schema::class, ['pdo' => $this->getPdo(), 'preload' => true]);

            $table = null;
            try {
                $table = $schema->getTable($this->tableName);
            } catch (Exception $e) {
                $table = $schema->addTable($this->tableName);
            }

            if ($table == null) {
                throw new Exception("Errors with table ". $this->tableName, 1);
            }

            $table = $this->addDBTableDefinition($table);

            $sql = $table->migrate();
            if ($this->getPdo()->exec($sql) === false) {
                throw new Exception("SQL Error: ".$this->getPdo()->errorInfo()[2], 1);
            }
        } catch (Exception $e) {
            if ($table instanceof Table) {
                echo "SQL query:" . $sql;
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
     *
     * @param  Table $table
     * @return Table
     */
    abstract public function addDBTableDefinition(Table $table);
}
