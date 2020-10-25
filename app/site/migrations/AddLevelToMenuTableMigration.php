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
use \Degami\SqlSchema\Table;

/**
 * "add level column" to menu table migration
 */
class AddLevelToMenuTableMigration extends DBMigration
{
    /**
     * @var string table name
     */
    protected $tableName = 'menu';

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '06_' . parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @param Table $table
     * @return Table
     * @throws DuplicateException
     */
    public function addDBTableDefinition(Table $table)
    {
        $table
            ->addColumn('level', 'INT', null, ['UNSIGNED']);

        return $table;
    }
}
