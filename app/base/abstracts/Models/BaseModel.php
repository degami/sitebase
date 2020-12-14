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

namespace App\Base\Abstracts\Models;

use ArrayAccess;
use DebugBar\DebugBar;
use Degami\Basics\Exceptions\BasicException;
use IteratorAggregate;
use LessQL\Result;
use \LessQL\Row;
use PDOStatement;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Base\Exceptions\InvalidValueException;
use \Exception;

/**
 * A wrapper for LessQL Row
 * @package App\Base\Abstracts\Models
 */
abstract class BaseModel extends ContainerAwareObject implements ArrayAccess, IteratorAggregate
{
    const ITEMS_PER_PAGE = 50;

    /**
     * @var Row database row
     */
    protected $db_row;

    /**
     * @var string table name
     */
    public $table_name;

    /**
     * @var boolean first save flag
     */
    protected $is_first_save;

    /**
     * @var array|null original model data
     */
    private $original_data = null;

    /**
     * @var array objects cache
     */
    protected static $loadedObjects = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Row|null $db_row
     * @throws InvalidValueException
     * @throws BasicException
     */
    public function __construct(ContainerInterface $container, $db_row = null)
    {
        parent::__construct($container);

        $name = $this->getTableName();
        if ($db_row instanceof Row) {
            $this->checkDbName($db_row);
            $this->setOriginalData($db_row->getData());
        } else {
            $db_row = $this->getDb()->createRow($name);
            $this->setOriginalData(null);
        }
        $this->setTableName($name);
        $this->setDbRow($db_row);
        $this->setIsFirstSave($this->isNew());
    }

    /**
     * gets object model name
     *
     * @return string
     */
    protected function getModelName(): string
    {
        return basename(str_replace("\\", "/", get_called_class()));
    }

    /**
     * gets table name
     *
     * @return string
     */
    protected function getTableName(): string
    {
        if (trim($this->table_name) != '') {
            return $this->table_name;
        }
        return static::defaultTableName();
    }

    /**
     * checks if Row object is from correct table
     *
     * @param Row $db_row
     * @return self
     * @throws InvalidValueException
     */
    private function checkDbName(Row $db_row): BaseModel
    {
        $name = $this->getTableName();
        if ($name != $db_row->getTable()) {
            throw new InvalidValueException('Invalid Row Resource');
        }
        return $this;
    }

    /**
     * returns an array of models, starting from a statement Result
     *
     * @param ContainerInterface $container
     * @param Result $stmt
     * @return array
     */
    public static function hidrateStatementResult(ContainerInterface $container, Result $stmt): array
    {
        $items = array_map(
            function ($el) use ($container) {
                return $container->make(static::class, ['db_row' => $el]);
            },
            $stmt->fetchAll()
        );

        return $items;
    }

    /**
     * basic select statement
     *
     * @param ContainerInterface $container
     * @param array $options
     * @return PDOStatement
     */
    public static function select(ContainerInterface $container, $options = []): PDOStatement
    {
        return $container->get('db')->select(static::defaultTableName(), $options);
    }

    /**
     * gets basic where statement for model
     *
     * @param ContainerInterface $container
     * @param array $condition
     * @param array $order
     * @return Result
     */
    protected static function getModelBasicWhere(ContainerInterface $container, $condition = [], $order = []): Result
    {
        if ($condition == null) {
            $condition = [];
        }
        $stmt = $container->get('db')->table(
            static::defaultTableName()
        )->where($condition);

        if (!empty($order) && is_array($order)) {
            foreach ($order as $column => $direction) {
                if (!in_array(strtoupper(trim($direction)), ['ASC', 'DESC'])) {
                    // not a direction, maybe not in form <columnaname> => <direction>, use direction as column
                    $stmt = $stmt->orderBy($direction);
                } else {
                    $stmt = $stmt->orderBy($column, strtoupper(trim($direction)) ?? 'ASC');
                }
            }
        } else {
            $stmt = $stmt->orderBy('id');
        }

        return $stmt;
    }

    /**
     * returns all found items
     *
     * @param ContainerInterface $container
     * @param array $condition
     * @param array $order
     * @return array
     */
    public static function all(ContainerInterface $container, $condition = [], $order = []): array
    {
        $items = static::hidrateStatementResult($container, static::getModelBasicWhere($container, $condition, $order));

        foreach ($items as $item) {
            static::$loadedObjects[static::defaultTableName()][$item->id] = $item;
        }

        return $items;
    }

    /**
     * return subset of found items (useful for paginate)
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param integer $page_size
     * @param array $condition
     * @param array $order
     * @return array
     */
    public static function paginate(ContainerInterface $container, Request $request, $page_size = self::ITEMS_PER_PAGE, $condition = [], $order = []): array
    {
        if ($condition == null) {
            $condition = [];
        }

        $stmt = static::getModelBasicWhere($container, $condition, $order);
        return static::paginateByStatement($container, $request, $stmt, $page_size);
    }

    /**
     * return subset of found items (useful for paginate)
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param Result $stmt
     * @return array
     */
    public static function paginateByStatement(ContainerInterface $container, Request $request, Result $stmt, $page_size = self::ITEMS_PER_PAGE): array
    {
        $page = $request->get('page') ?? 0;
        $start = (int)$page * $page_size;

        $total = (clone $stmt)->count();

        $items = static::hidrateStatementResult($container, $stmt->limit($page_size, $start));

        foreach ($items as $item) {
            static::$loadedObjects[static::defaultTableName()][$item->id] = $item;
        }

        return ['items' => $items, 'page' => $page, 'total' => $total];
    }

    /**
     * finds elements
     *
     * @param ContainerInterface $container
     * @param array|string $condition
     * @param array $order
     * @return array
     */
    public static function where(ContainerInterface $container, $condition, $order = []): array
    {
        $items = static::hidrateStatementResult($container, static::getModelBasicWhere($container, $condition, $order));

        foreach ($items as $item) {
            static::$loadedObjects[static::defaultTableName()][$item->id] = $item;
        }

        return $items;
    }

    /**
     * gets model table name
     *
     * @return string
     */
    public static function defaultTableName(): string
    {
        $path = explode('\\', static::class);
        return strtolower(static::pascalCaseToSnakeCase(array_pop($path)));
    }

    /**
     * fills empty model with data
     *
     * @param integer|Row $id
     * @return self
     * @throws InvalidValueException
     * @throws BasicException
     */
    public function fill($id): BaseModel
    {
        if ($id instanceof Row) {
            $this->checkDbName($id);
            $this->setDbRow($id);
            $this->setTableName($this->db_row->getTable());
        } elseif (is_numeric($id)) {
            $this->setTableName($this->getTableName());
            $db_row = $this->getDb()->table(static::defaultTableName(), $id);
            $this->setDbRow($db_row);
        }
        $this->setIsFirstSave($this->isNew());

        return $this;
    }

    /**
     * checks if model is loaded
     *
     * @return boolean
     */
    public function isLoaded(): bool
    {
        return ($this->db_row instanceof Row) && $this->db_row->exists();
    }

    /**
     * checks if model is new
     *
     * @return boolean
     */
    public function isNew(): bool
    {
        return !$this->isLoaded();
    }

    /**
     * ensures model is loaded
     *
     * @return self
     * @throws Exception
     */
    public function checkLoaded(): BaseModel
    {
        if (!$this->isLoaded()) {
            throw new Exception($this->getModelName() . " is not loaded", 1);
        }

        return $this;
    }

    /**
     * resets model
     *
     * @return self
     * @throws BasicException
     */
    public function reset(): BaseModel
    {
        if ($this->db_row->exists()) {
            $db_row = $this->getDb()->table($this->db_row->getTable(), $this->db_row->getOriginalId());
            if ($db_row) {
                $this->setDbRow($db_row);
                $this->setOriginalData($db_row->getData());
            }
        }
        $this->setIsFirstSave($this->isNew());

        return $this;
    }

    /**
     * loads model by id
     *
     * @param ContainerInterface $container
     * @param integer $id
     * @param bool $reset
     * @return self
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function load(ContainerInterface $container, $id, $reset = false): BaseModel
    {
        /** @var DebugBar $debugbar */
        $debugbar = $container->get('debugbar');

        $measure_key = 'load model: ' . static::defaultTableName();

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }
        if (isset(static::$loadedObjects[static::defaultTableName()][$id]) && !$reset) {
            if (getenv('DEBUG')) {
                $debugbar['time']->stopMeasure($measure_key);
            }
            return static::$loadedObjects[static::defaultTableName()][$id];
        }

        $db_row = $container->get('db')->table(static::defaultTableName(), $id);

        static::$loadedObjects[static::defaultTableName()][$id] = new static($container, $db_row);

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }
        return static::$loadedObjects[static::defaultTableName()][$id];
    }

    /**
     * loads multiple models by id
     *
     * @param ContainerInterface $container
     * @param array $ids
     * @param bool $reset
     * @return array
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function loadMultiple(ContainerInterface $container, $ids, $reset = false): array
    {
        $already_loaded = [];
        if (!$reset && isset(static::$loadedObjects[static::defaultTableName()])) {
            $new_ids = array_diff(array_filter($ids, function ($el) {
                return is_numeric($el) && $el > 0;
            }), array_keys(static::$loadedObjects[static::defaultTableName()]));

            $already_loaded = array_diff($ids, $new_ids);
            $ids = $new_ids;
        } else {
            $ids = array_filter($ids, function ($el) {
                return is_numeric($el) && $el > 0;
            });
        }

        return
            (!empty($ids) ? static::loadMultipleByCondition($container, ['id' => $ids], $reset) : []) +
            (!empty($already_loaded) ? array_intersect_key(static::$loadedObjects[static::defaultTableName()], array_flip($already_loaded)) : []);
    }

    /**
     * loads model by condition
     *
     * @param ContainerInterface $container
     * @param array $condition
     * @return self
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function loadByCondition(ContainerInterface $container, $condition): BaseModel
    {
        $db_row = $container->get('db')->table(static::defaultTableName())->where($condition)->limit(1)->fetch();
        return static::$loadedObjects[static::defaultTableName()][$db_row->id] = new static($container, $db_row);
    }

    /**
     * loads multiple models by condition
     *
     * @param ContainerInterface $container
     * @param array $condition
     * @param bool $reset
     * @return array
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function loadMultipleByCondition(ContainerInterface $container, $condition, $reset = false): array
    {
        $ids = [];
        foreach ($container->get('db')->table(static::defaultTableName())->where($condition)->fetchAll() as $db_row) {
            $ids[] = intval($db_row->id);
            /** @var Result $db_row */
            if (!isset($loadedObjects[static::defaultTableName()][$db_row->id]) || $reset) {
                static::$loadedObjects[static::defaultTableName()][$db_row->id] = new static($container, $db_row);
            }
        }

        return array_intersect_key(static::$loadedObjects[static::defaultTableName()], array_flip($ids));
    }


    /**
     * gets new empty model
     *
     * @param ContainerInterface $container
     * @param array $initial_data
     * @return static
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function new(ContainerInterface $container, $initial_data = []): BaseModel
    {
        $db_row = $container->get('db')->createRow(static::defaultTableName());
        $db_row->setData($initial_data);
        return new static($container, $db_row);
    }

    /**
     * loads model by field - value pair
     *
     * @param ContainerInterface $container
     * @param string $field
     * @param string $value
     * @return self
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function loadBy(ContainerInterface $container, $field, $value): BaseModel
    {
        return static::loadByCondition($container, [$field => $value]);
    }

    /**
     * {@inheritdocs}
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->db_row[$key];
    }

    /**
     * {@inheritdocs}
     * @param $key
     * @param $value
     * @return BaseModel
     */
    public function __set($key, $value): BaseModel
    {
        $this->db_row[$key] = $value;
        return $this;
    }

    /**
     * {@inheritdocs}
     * @param $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return isset($this->db_row[$name]);
    }

    /**
     * {@inheritdocs}
     * @param $name
     */
    public function __unset($name)
    {
        unset($this->db_row[$name]);
    }

    /**
     * {@inheritdocs}
     * @param $name
     * @param $arguments
     * @return BaseModel|bool|mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (!($this->db_row instanceof Row)) {
            throw new Exception("No row loaded", 1);
        }

        if ($name != 'getData') {
            $method_name = static::pascalCaseToSnakeCase($name);
            if (in_array($method = strtolower(substr($method_name, 0, 4)), ['get_', 'has_', 'set_'])) {
                $prop = substr($method_name, 4);
                switch (substr($method, 0, 3)) {
                    case 'get':
                        return $this->{$prop};
                    case 'set':
                        $this->{$prop} = reset($arguments);
                        return $this;
                    case 'has':
                        return isset($this->{$prop});
                }
            }
        }

        return call_user_func_array([$this->db_row, $name], $arguments);
    }

    /**
     * gets model's data
     *
     * @param null $column
     * @return array|mixed|null
     */
    public function getData($column = null)
    {
        $data = $this->db_row->getData();

        if ($column != null) {
            return $data[$column] ?? null;
        }

        return $data;
    }

    /**
     * {@inheritdocs}
     */
    public function getIterator()
    {
        return $this->db_row->getIterator();
    }

    /**
     * {@inheritdocs}
     * @param $offset
     * @param $value
     * @return BaseModel
     */
    public function offsetSet($offset, $value): BaseModel
    {
        return $this->__set($offset, $value);
    }

    /**
     * {@inheritdocs}
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * {@inheritdocs}
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * {@inheritdocs}
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritdocs}
     */
    public function current()
    {
        return $this->db_row->current();
    }

    /**
     * {@inheritdocs}
     */
    public function key()
    {
        return $this->db_row->key();
    }

    /**
     * {@inheritdocs}
     */
    public function next()
    {
        $this->db_row->next();
    }

    /**
     * {@inheritdocs}
     */
    public function rewind()
    {
        $this->db_row->rewind();
    }

    /**
     * {@inheritdocs}
     */
    public function valid()
    {
        return $this->db_row->valid();
    }

    /**
     * saves model on db
     */
    public function save()
    {
        $this->persist();
    }

    /**
     * saves model on db
     *
     * @return self
     */
    public function persist(): BaseModel
    {
        $this->prePersist();

        if (!$this->db_row->exists() && array_key_exists('created_at', $this->db_row->getData())) {
            $this->db_row->created_at = date("Y-m-d H:i:s", time());
        }
        if (array_key_exists('updated_at', $this->db_row->getData())) {
            $this->db_row->updated_at = date("Y-m-d H:i:s", time());
        }
        $this->db_row->update($this->getData());

        $this->postPersist();

        $this->setIsFirstSave(false);

        $this->original_data = $this->db_row->getData();

        return $this;
    }

    /**
     * pre persist hook
     *
     * @return self
     */
    public function prePersist(): BaseModel
    {
        return $this;
    }

    /**
     * post persist hook
     *
     * @return self
     */
    public function postPersist(): BaseModel
    {
        return $this;
    }

    /**
     * removes model from db
     */
    public function delete()
    {
        $this->remove();
    }

    /**
     * removes model from db
     *
     * @return self
     */
    public function remove(): BaseModel
    {
        $this->preRemove();

        $this->db_row->delete();

        $this->postRemove();

        return $this;
    }

    /**
     * pre remove hook
     *
     * @return self
     */
    public function preRemove(): BaseModel
    {
        return $this;
    }

    /**
     * post remove hook
     *
     * @return self
     */
    public function postRemove(): BaseModel
    {
        return $this;
    }

    /**
     * sets is first save flag
     *
     * @param $is_first_save
     * @return $this
     */
    public function setIsFirstSave($is_first_save): BaseModel
    {
        $this->is_first_save = $is_first_save;
        return $this;
    }

    /**
     * is first save flag
     *
     * @return bool
     */
    public function isFirstSave(): bool
    {
        return ($this->is_first_save == true);
    }

    /**
     * sets model's original data
     *
     * @param $original_data
     * @return $this
     */
    protected function setOriginalData($original_data): BaseModel
    {
        $this->original_data = $original_data;
        return $this;
    }

    /**
     * gets model's original data
     *
     * @param null $key
     * @return array|null
     */
    protected function getOriginalData($key = null): ?array
    {
        if ($key != null && array_key_exists($key, $this->original_data)) {
            return $this->original_data[$key];
        }

        return $this->original_data;
    }

    /**
     * @return Row database row
     */
    public function getDbRow(): Row
    {
        return $this->db_row;
    }

    /**
     * @param Row database row $db_row
     *
     * @return self
     */
    public function setDbRow($db_row): BaseModel
    {
        $this->db_row = $db_row;

        return $this;
    }

    /**
     * @param string table name $table_name
     *
     * @return self
     */
    public function setTableName($table_name): BaseModel
    {
        $this->table_name = $table_name;

        return $this;
    }

    /**
     * gets model's changed data
     *
     * @return array|null
     */
    public function getChangedData(): ?array
    {
        if ($this->getOriginalData() == null) {
            return $this->getData();
        }

        $changed = [];

        foreach ($this->getData() as $key => $value) {
            if ($this->getOriginalData($key) != $value) {
                $changed[$key] = ['now' => $value, 'original' => $this->getOriginalData($key)];
            }
        }

        return $changed;
    }
}
