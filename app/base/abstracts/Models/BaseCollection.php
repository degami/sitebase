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

namespace App\Base\Abstracts\Models;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Tools\DataCollector\CollectionDataCollector;
use ArrayAccess;
use DebugBar\DebugBar;
use DI\DependencyException;
use DI\NotFoundException;
use IteratorAggregate;
use LessQL\Result;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A LessQL Collection
 * @package App\Base\Abstracts\Models
 */
class BaseCollection extends ContainerAwareObject implements ArrayAccess, IteratorAggregate
{
    public const ITEMS_PER_PAGE = 50;

    /**
     * @var array collection loaded items
     */
    protected array $items = [];

    /**
     * @var Result|null collection statement
     */
    protected ?Result $stmt = null;


    public function __construct(
        protected ContainerInterface $container,
        protected string $className
    ) {
        if (!is_subclass_of($className, BaseModel::class, true)) {
            throw new InvalidValueException("$className is not a subclass of BaseModel");
        }

        parent::__construct($container);

        if ($this->getEnv('DEBUG')) {
            /** @var DebugBar $debugbar */
            $debugbar = $this->getContainer()->get('debugbar');
            if (!$debugbar->hasCollector(CollectionDataCollector::NAME)) {
                $debugbar->addCollector(new CollectionDataCollector());
            }
        }

        $this->reset();
    }

    /**
     * gets collection objects table name
     * 
     * @return string
     */
    public function getTableName() : string
    {
        return $this->containerCall([$this->className, 'defaultTableName']);
    }

    /**
     * get collection select object
     * 
     * @return Result
     */
    public function getSelect() : Result 
    {
        if (is_null($this->stmt)) {
            $this->stmt = $this->getDb()->table(
                $this->getTableName()
            );
        }

        return $this->stmt;
    }

    public function addSelect($select): static
    {
        $this->stmt = $this->getSelect()->select($select);
        return $this;
    }

    public function addGroupBy($groupBy): static
    {
        $this->stmt = $this->getSelect()->groupBy($groupBy);
        return $this;
    }

    public function addHaving($having): static
    {
        $this->stmt = $this->getSelect()->having($having);
        return $this;
    }

    /**
     * adds condition to collection
     *
     * @param array $condition
     * @return static
     */
    public function addCondition($condition = []): static
    {
        if ($condition == null) {
            $condition = [];
        }

        $conditions_where = [];
        $conditions_wherenot = [];

        if (!is_array($condition)) {
             $condition = [$condition];
        }

        foreach ($condition as $key => $value) {
            if (!is_numeric($key)) {
                if (preg_match("/:not$/", $key)) {
                    $key = preg_replace("/:not$/", "", $key);
                    $conditions_wherenot[$key] = $value;
                } else {
                    $conditions_where[$key] = $value;
                }
            } else {
                $this->stmt = $this->getSelect()->where($value);
            }
        }

        if (!empty($conditions_where)) {
            $this->stmt = $this->getSelect()->where($conditions_where);
        }

        if (!empty($conditions_wherenot)) {
            $this->stmt = $this->getSelect()->whereNot($conditions_wherenot);
        }

        return $this;
    }

    /**
     * adds order to collection
     *
     * @param array|string $order
     * @return static
     */
    public function addOrder($order = []) : static 
    {
        $tableColumns = $this->containerCall([$this->className, 'getTableColumns']);
        if (!empty($order) && is_array($order)) {
            foreach ($order as $column => $direction) {
                if (!in_array(strtoupper(trim($direction)), ['ASC', 'DESC'])) {
                    // not a direction, maybe not in form <columnaname> => <direction>, use direction as column
                    if (in_array($direction, $tableColumns)) {
                        $this->stmt = $this->getSelect()->orderBy($direction);
                    } else {
                        $this->stmt = $this->getSelect()->orderBy($direction, true);
                    }
                } else {
                    if (in_array($column, $tableColumns)) {
                        $this->stmt = $this->getSelect()->orderBy($column, strtoupper(trim($direction)));
                    } else {
                        $this->stmt = $this->getSelect()->orderBy($column .' '. strtoupper(trim($direction)), true);
                    }
                }
            }
        } else if (is_string($order) && !empty($order)) {
            if (in_array($order, $tableColumns)) {
                $this->stmt = $this->getSelect()->orderBy($order);
            } else {
                $this->stmt = $this->getSelect()->orderBy($order, true);
            }
        } else {
            $this->stmt = $this->getSelect()->orderBy($this->containerCall([$this->className, 'getKeyField']));
        }

        return $this;
    }

    /**
     * limit results
     * 
     * @param int $page_size
     * @param int $start
     * @return static
     */
    public function limit(int $page_size, int $start = 0) : static
    {
        $this->stmt = $this->getSelect()->limit($page_size, $start);
        return $this;
    }

    /**
     * finds elements
     *
     * @param array|string $condition
     * @param array $order
     * @param int!null $page_size
     * @param int $start
     * @return static
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function where(array|string $condition, array $order = [], ?int $page_size = null, int $start = 0): static
    {
        $this->addCondition($condition);
        if (!empty($order)) {
            $this->addOrder($order);
        }
        if (is_int($page_size) && $page_size > 0) {
            $this->limit($page_size, $start);
        }
        return $this;
    }

    /**
     * loads collection items
     * 
     * @return static
     */
    public function load() : static
    {
        if (empty($this->items)) {
            /** @var DebugBar $debugbar */
            $debugbar = $this->getDebugbar();

            $measure_key = 'load collection: ' . $this->getTableName();

            if (getenv('DEBUG')) {
                $debugbar['time']->startMeasure($measure_key);
            }

            $before = memory_get_usage();
            $this->items = [];
            foreach($this->containerCall([$this->className, 'hydrateStatementResult'], ['stmt' => $this->getSelect()]) as $item) {
                /** @var BaseModel $item */
                $this->items[$item->getKeyFieldValue()] = $item;
            }
            $after = memory_get_usage();

            if (getenv('DEBUG')) {
                $debugbar['time']->stopMeasure($measure_key);

                if ($debugbar->hasCollector(CollectionDataCollector::NAME)) {
                    /** @var CollectionDataCollector $dataCollector */
                    $dataCollector = $debugbar->getCollector(CollectionDataCollector::NAME);
                    $dataCollector->addElements($this->className, array_keys($this->items), ($after - $before));
                }
            }
        }

        return $this;
    }

    public function fetchDbRow()
    {
        return $this->getSelect()->fetch();
    }

    public function fetchAllDbRows()
    {
        return $this->getSelect()->fetchall();
    }

    public function getFirst()
    {
        $stmt = $this->getSelect()->limit(1);
        $item = $this->containerCall([$this->className, 'hydrateStatementResult'], ['stmt' => $stmt]); 
        $item = reset($item);

        if (!$item) {
            return null;
        }

        return $item;
    }

    /**
     * resets collection
     * 
     * @return static
     */
    public function reset() : static
    {
        $this->items = [];
        $this->stmt = null;

        return $this;
    }

    /**
     * gets collection items
     * 
     * @return array
     */
    public function getItems() : array
    {
        $this->load();
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->load();
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->load();
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->load();
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->load();
        unset($this->items[$offset]);
    }

    public function getIterator(): \Traversable|\ArrayIterator
    {
        $this->load();
        return new \ArrayIterator($this->items);
    }

    /**
     * persists collection items
     * 
     * @return static
     */
    public function persist() : static 
    {
        /** @var DebugBar $debugbar */
        $debugbar = $this->getDebugbar();

        $measure_key = 'persist collection: ' . $this->getTableName();

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }

        foreach ($this->getItems() as $item) {
            $item->persist();
        }

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }

        return $this;
    }

    public function save() : static 
    {
        return $this->persist();
    }

    /**
     * removes collection items
     * 
     * @return static
     */
    public function remove() : static
    {
        /** @var DebugBar $debugbar */
        $debugbar = $this->getDebugbar();

        $measure_key = 'remove collection: ' . $this->getTableName();

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }

        foreach ($this->getItems() as $item) {
            $item->remove();
        }

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }

        return $this;
    }

    public function delete() : static
    {
        return $this->remove();
    }

    public function count() : int
    {
        $stmt = $this->getSelect();
        return (clone $stmt)->removePart('limitCount')->removePart('limitOffset')->count();
    }

    /**
     * return subset of found items (useful for paginate)
     *
     * @param Request $request
     * @param int $page_size
     * @param array $condition
     * @param array $order
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function paginate(Request $request, $page_size = self::ITEMS_PER_PAGE): array
    {
        /** @var DebugBar $debugbar */
        $debugbar = $this->getDebugbar();

        $measure_key = 'paginate collection: ' . $this->getTableName();

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $page = $request->get('page') ?? 0;
        $start = (int)$page * $page_size;

        $total = $this->count();
        
        $this->items = [];
        foreach ($this->containerCall([$this->className, 'hydrateStatementResult'], ['stmt' => $this->getSelect()->limit($page_size, $start)]) as $item) {
            $this->items[$item->getId()] = $item;
        }

        $out = ['items' => $this->getItems(), 'page' => $page, 'total' => $total, 'page_size' => $page_size];

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }

        return $out;
    }
}