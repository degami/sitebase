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

namespace App\Base\Overrides\Migrations;

use Closure;
use \Genkgo\Migrations\Factory as GenkgoMigrationFactory;
use \Genkgo\Migrations\AdapterInterface;
use \InvalidArgumentException;

/**
 * Migration factory override
 */
class Factory extends GenkgoMigrationFactory
{
    /**
     * @var AdapterInterface adapter
     */
    private $adapter;

    /**
     * @var Closure class loader
     */
    private $classLoader;

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
}
