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

namespace App\Base\Abstracts\Models;

use App\App;
use ArrayAccess;
use DebugBar\DebugBar;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use IteratorAggregate;
use LessQL\Database;
use LessQL\Result;
use LessQL\Row;
use PDOStatement;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Exceptions\InvalidValueException;
use Exception;

/**
 * A wrapper for LessQL Row
 * @package App\Base\Abstracts\Models
 */
abstract class BaseModel extends ContainerAwareObject implements ArrayAccess, IteratorAggregate
{
    /**
     * @var Row database row
     */
    protected Row $db_row;

    /**
     * @var string|null table name
     */
    public ?string $table_name = null;

    /**
     * @var bool first save flag
     */
    protected bool $is_first_save;

    /**
     * @var array|null original model data
     */
    private ?array $original_data = null;

    /**
     * @var array objects cache
     */
    protected static array $loadedObjects = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Row|null $db_row
     * @throws InvalidValueException
     * @throws BasicException
     */
    public function __construct(
        protected ContainerInterface $container, 
        Row $db_row = null
    ) {
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
        if (!$this->isNew()) {
            $this->postLoad();
        }
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
     * @param Result $stmt
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function hydrateStatementResult(Result $stmt): array
    {
        $container = App::getInstance()->getContainer();
        return array_map(
            function ($el) use ($container) {
                return $container->make(static::class, ['db_row' => $el]);
            },
            $stmt->fetchAll()
        );
    }

    public static function getCollection() : BaseCollection
    {
        $container = App::getInstance()->getContainer();
        return $container->make(BaseCollection::class, ['className' => static::class]);
    }

    /**
     * basic select statement
     *
     * @param array $options
     * @return PDOStatement
     */
    public static function select($options = []): PDOStatement
    {
        $container = App::getInstance()->getContainer();
        return $container->get('db')->select(static::defaultTableName(), $options);
    }

    /**
     * gets basic where statement for model
     *
     * @param array $condition
     * @param array $order
     * @return Result
     */
    protected static function getModelBasicWhere($condition = [], $order = []): Result
    {
        $container = App::getInstance()->getContainer();
        if ($condition == null) {
            $condition = [];
        }

        $conditions_where = [];
        $conditions_wherenot = [];

        if (!is_array($condition)) {
             $condition = [$condition];
        }

        /** @var Result $stmt */
        $stmt = $container->get('db')->table(
            static::defaultTableName()
        );

        foreach ($condition as $key => $value) {
            if (!is_numeric($key)) {
                if (preg_match("/:not$/", $key)) {
                    $key = preg_replace("/:not$/", "", $key);
                    $conditions_wherenot[$key] = $value;
                } else {
                    $conditions_where[$key] = $value;
                }
            } else {
                $stmt = $stmt->where($value);
            }
        }

        if (!empty($conditions_where)) {
            $stmt = $stmt->where($conditions_where);
        }

        if (!empty($conditions_wherenot)) {
            $stmt = $stmt->whereNot($conditions_wherenot);
        }

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
     * @param int|Row $id
     * @return self
     * @throws InvalidValueException
     * @throws BasicException
     */
    public function fill(Row|int $id): BaseModel
    {
        if ($id instanceof Row) {
            $this->checkDbName($id);
            $this->setDbRow($id);
            $this->setTableName($this->getDbRow()->getTable());
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
     * @return bool
     */
    public function isLoaded(): bool
    {
        return ($this->getDbRow() instanceof Row) && $this->getDbRow()->exists();
    }

    /**
     * checks if model is new
     *
     * @return bool
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
        if ($this->getDbRow()->exists()) {
            $db_row = $this->getDb()->table($this->getDbRow()->getTable(), $this->getDbRow()->getOriginalId());
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
     * @param int $id
     * @param bool $reset
     * @return self
     */
    public static function load($id, $reset = false): BaseModel
    {
        $container = App::getInstance()->getContainer();

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

        $object = $container->call([static::class, 'loadByCondition'], ['condition' => ['id' => $id]]);

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }
        return $object;
    }

    /**
     * loads model by condition
     *
     * @param array $condition
     * @return self|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function loadByCondition(array $condition): ?BaseModel
    {
        $container = App::getInstance()->getContainer();

        /** @var DebugBar $debugbar */
        $debugbar = $container->get('debugbar');

        $measure_key = 'loadByCondition model: ' . static::defaultTableName();

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $stmt = static::getModelBasicWhere($condition);
        $db_row = $stmt->limit(1)->fetch();
        if (!$db_row || !$db_row->id) {
            throw new BasicException('Model not found');
        }

        static::$loadedObjects[static::defaultTableName()][$db_row->id] = $container->make(static::class, ['db_row' => $db_row]);

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }

        return static::$loadedObjects[static::defaultTableName()][$db_row->id];
    }

    /**
     * gets new empty model
     *
     * @param array $initial_data
     * @return static
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function new($initial_data = []): BaseModel
    {
        $container = App::getInstance()->getContainer();

        $db_row = $container->get('db')->createRow(static::defaultTableName());
        $db_row->setData($initial_data);
        return new static($container, $db_row);
    }

    /**
     * loads model by field - value pair
     *
     * @param string $field
     * @param mixed $value
     * @return self
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function loadBy(string $field, $value): BaseModel
    {
        return static::loadByCondition([$field => $value]);
    }

    /**
     * post load hook
     *
     * @return self
     */
    public function postLoad(): BaseModel
    {
        return $this;
    }

    /**
     * {@inheritdocs}
     * @param $key
     * @return mixed
     */
    public function __get($key): mixed
    {
        return $this->db_row[$key];
    }

    /**
     * {@inheritdocs}
     * @param $key
     * @param $value
     * @return void
     */
    public function __set($key, $value): void
    {
        $this->db_row[$key] = $value;
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
     * @return void
     */
    public function __unset($name): void
    {
        unset($this->db_row[$name]);
    }

    /**
     * {@inheritdocs}
     * @param string $name
     * @param $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call(string $name, mixed $arguments): mixed
    {
        if (!($this->getDbRow() instanceof Row)) {
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

        return call_user_func_array([$this->getDbRow(), $name], $arguments);
    }

    /**
     * gets model's data
     *
     * @param null $column
     * @return mixed
     */
    public function getData($column = null): mixed
    {
        $data = $this->getDbRow()->getData();

        if ($column != null) {
            return $data[$column] ?? null;
        }

        return $data;
    }

    /**
     * {@inheritdocs}
     */
    public function getIterator(): \Traversable|\ArrayIterator
    {
        return $this->getDbRow()->getIterator();
    }

    /**
     * {@inheritdocs}
     * @param $offset
     * @param $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
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
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->__unset($offset);
    }

    /**
     * {@inheritdocs}
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritdocs}
     */
    public function current()
    {
        return $this->getDbRow()->current();
    }

    /**
     * {@inheritdocs}
     */
    public function key()
    {
        return $this->getDbRow()->key();
    }

    /**
     * {@inheritdocs}
     */
    public function next()
    {
        $this->getDbRow()->next();
    }

    /**
     * {@inheritdocs}
     */
    public function rewind()
    {
        $this->getDbRow()->rewind();
    }

    /**
     * {@inheritdocs}
     */
    public function valid()
    {
        return $this->getDbRow()->valid();
    }

    /**
     * saves model on db
     *
     * @return self
     * @throws BasicException
     */
    public function save(): BaseModel
    {
        return $this->persist();
    }

    /**
     * saves model on db
     *
     * @return self
     * @throws BasicException
     */
    public function persist(): BaseModel
    {
        $debugbar = $this->getDebugbar();

        $measure_key = 'persist model: ' . static::defaultTableName();

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $this->prePersist();

        if (!$this->getDbRow()->exists() && array_key_exists('created_at', $this->getDbRow()->getData()) && $this->getData('created_at') == null) {
            $this->getDbRow()->created_at = date("Y-m-d H:i:s", time());
        }
        if (array_key_exists('updated_at', $this->getDbRow()->getData())) {
            $this->getDbRow()->updated_at = date("Y-m-d H:i:s", time());
        }
        $this->getDbRow()->update($this->getData());

        $this->postPersist();

        $this->setIsFirstSave(false);

        $this->original_data = $this->getDbRow()->getData();

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }

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
     * @return self
     * @throws BasicException
     */
    public function delete(): BaseModel
    {
        return $this->remove();
    }

    /**
     * removes model from db
     *
     * @return self
     * @throws BasicException
     */
    public function remove(): BaseModel
    {
        $debugbar = $this->getDebugbar();

        $measure_key = 'delete model: ' . static::defaultTableName();

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $this->preRemove();

        $this->getDbRow()->delete();

        $this->postRemove();

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }

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
     * @param array|null $original_data
     * @return $this
     */
    protected function setOriginalData(?array $original_data): BaseModel
    {
        $this->original_data = $original_data;
        return $this;
    }

    /**
     * gets model's original data
     *
     * @param null $key
     * @return mixed
     */
    protected function getOriginalData($key = null): mixed
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
