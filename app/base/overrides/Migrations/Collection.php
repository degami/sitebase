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

namespace App\Base\Overrides\Migrations;

use Genkgo\Migrations\Log;
use Genkgo\Migrations\AdapterInterface;
use Genkgo\Migrations\MigrationInterface;
use Genkgo\Migrations\AlreadyMigratedException;
use Genkgo\Migrations\NotReadyToMigrateException;
use InvalidArgumentException;

/**
 * Overrides migrations collection in order to have them sorted
 */
class Collection
{
    /**
     * @var AdapterInterface adapter
     */
    private $adapter;

    /**
     * @var array migrations collection
     */
    private $list = [];

    /**
     * @var string namespace
     */
    private $namespace;

    /**
     * constructor
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * attach migration
     *
     * @param MigrationInterface $migration
     */
    public function attach(MigrationInterface $migration) : void
    {
        $this->list[] = $migration;
    }

    /**
     * detach migration
     *
     * @param MigrationInterface $migration
     */
    public function detach(MigrationInterface $migration) : void
    {
        if (($key = array_search($migration, $this->list)) !== false) {
            unset($this->list[$key]);
        } else {
            throw new InvalidArgumentException('Migration not in collection');
        }
    }

    /**
     * sets namespace
     *
     * @param  $namespace
     * @return $this
     */
    public function setNamespace($namespace): Collection
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * gets namespace
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * do the migration
     *
     * @param int $direction
     * @return Log
     */
    public function migrate($direction = MigrationInterface::DIRECTION_UP): Log
    {
        $result = new Log();

        usort(
            $this->list,
            function ($item1, $item2) {
                $class1 = $item1->getName();
                $class2 = $item2->getName();
                return strcmp($class1, $class2);
            }
        );

        if ($direction == MigrationInterface::DIRECTION_DOWN) {
            $this->list = array_reverse($this->list);
        }

        foreach ($this->list as $item) {
            try {
                $this->execute($item, $direction);
                $result->attach($item);
            } catch (AlreadyMigratedException $e) {
                /**
                 * we will not execute migrations that are already executed
                 */
            } catch (NotReadyToMigrateException $e) {
                /**
                 * we will not execute migrations that are not ready to be executed
                 */
            }
        }

        return $result;
    }

    /**
     * executes a single migration
     *
     * @param MigrationInterface $migration
     * @param $direction
     */
    private function execute(MigrationInterface $migration, $direction) : void
    {
        if ($direction == MigrationInterface::DIRECTION_UP) {
            $this->adapter->upgrade(
                $this->getNamespace(),
                $migration
            );
        } elseif ($direction == MigrationInterface::DIRECTION_DOWN) {
            $this->adapter->downgrade(
                $this->getNamespace(),
                $migration
            );
        }
    }

    /**
     * gets migrations count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->list);
    }
}
