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

use \Genkgo\Migrations\MigrationInterface;
use \Verraes\ClassFunctions\ClassFunctions;
use \Psr\Container\ContainerInterface;

/**
 * Base for migration objects
 */
abstract class Migration extends ContainerAwareObject implements MigrationInterface
{
    /**
     * do the migration
     *
     * @return void
     */
    abstract public function up();

    /**
     * undo the migration
     *
     * @return void
     */
    abstract public function down();
    
    /**
     * gets migration name
     *
     * @return string
     */
    public function getName()
    {
        return ClassFunctions::short($this);
    }
}
