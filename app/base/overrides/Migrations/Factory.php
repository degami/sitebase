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

use Closure;
use Genkgo\Migrations\AdapterInterface;
use Genkgo\Migrations\Utils\FileList;
use Genkgo\Migrations\MigrationInterface;
use InvalidArgumentException;

/**
 * Migration factory override
 */
class Factory
{
    /**
     * @var AdapterInterface adapter
     */
    private AdapterInterface $adapter;

    /**
     * @var Closure class loader
     */
    private Closure $classLoader;

    /**
     * Factory constructor.
     *
     * @param AdapterInterface $adapter
     * @param Closure|null $classLoader
     */
    public function __construct(AdapterInterface $adapter, Closure $classLoader = null)
    {
        $this->adapter = $adapter;
        $this->setClassLoader($classLoader);
    }

    public function setClassLoader(\Closure $classLoader = null): void
    {
        if (null === $classLoader) {
            $classLoader = fn ($classname) => new $classname;
        }

        $this->classLoader = $classLoader;
    }

    /**
     * gets a new collection
     *
     * @param string $namespace
     * @return Collection
     */
    public function newList($namespace = '\\'): Collection
    {
        if (substr($namespace, -1) !== '\\') {
            throw new InvalidArgumentException('Namespace incorrect, follow psr-4 namespace rules. Do not forget trailing slashes');
        }
        return (new Collection($this->adapter))->setNamespace($namespace);
    }

    public function newListFromDirectory(string $directory, string $namespace = '\\'): Collection
    {
        $collection = $this->newList($namespace);
        $classloader = $this->classLoader;

        $files = FileList::fromDirectory($directory);
        foreach ($files as $file) {
            require_once $file;
            $classname = \basename($file, '.php');
            $qualifiedClassName = $namespace . $classname;

            if (\is_a($qualifiedClassName, MigrationInterface::class, true)) {
                $collection->attach($classloader($qualifiedClassName));
            }
        }

        return $collection;
    }
}
