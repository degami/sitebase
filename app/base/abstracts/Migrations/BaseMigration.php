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

namespace App\Base\Abstracts\Migrations;

use Genkgo\Migrations\MigrationInterface;
use Verraes\ClassFunctions\ClassFunctions;
use App\Base\Abstracts\ContainerAwareObject;

/**
 * Base for migration objects
 */
abstract class BaseMigration extends ContainerAwareObject implements MigrationInterface
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
    public function getName(): string
    {
        return ClassFunctions::short($this);
    }
}
