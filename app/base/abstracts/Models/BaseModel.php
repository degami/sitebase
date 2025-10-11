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

use App\App;
use ArrayAccess;
use DebugBar\DebugBar;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use IteratorAggregate;
use LessQL\Result;
use LessQL\Row;
use PDOStatement;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Exceptions\NotFoundException as ExceptionsNotFoundException;
use App\Base\Models\GuestUser;
use App\Base\Tools\Search\Manager as SearchManager;
use Exception;
use Throwable;
use Degami\Basics\Traits\ToolsTrait as BasicToolsTrait;
use RuntimeException;
use Degami\SqlSchema\IndexColumn;
use ReflectionClass;
use ReflectionMethod;
use App\Base\Models\ModelVersion;

/**
 * A wrapper for LessQL Row
 */
abstract class BaseModel implements ArrayAccess, IteratorAggregate
{
    use BasicToolsTrait;

    /**
     * @var Row database row
     */
    protected ?Row $db_row = null;

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

    protected static string|array $keyField = 'id';

    /**
     * {@inheritdoc}
     *
     * @param Row|null $db_row
     * @throws InvalidValueException
     * @throws BasicException
     */
    public function __construct(
        ?Row $db_row = null
    ) {
        $name = $this->getTableName();
        if ($db_row instanceof Row) {
            $this->checkDbName($db_row);
            $this->setOriginalData($db_row->getData());
        } else {
            /** @var Row $db_row */
            $db_row = App::getInstance()->getDb()->createRow($name);
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
        return static::getClassBasename(get_called_class());
    }

    /**
     * gets table name
     *
     * @return string
     */
    protected function getTableName(): string
    {
        if (trim((string) $this->table_name) != '') {
            return (string) $this->table_name;
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
        return array_map(
            function ($el) {
                return App::getInstance()->containerMake(static::class, ['db_row' => $el]);
            },
            $stmt->fetchAll()
        );
    }

    /**
     * returns a Model collection
     */
    public static function getCollection() : BaseCollection
    {
        return App::getInstance()->containerMake(BaseCollection::class, ['className' => static::class]);
    }

    /**
     * returns a Model collection using SearchManager for finding elements
     */
    public static function getSearchCollection(array $conditions = []) : BaseCollection
    {
        if (static::getKeyField() !== 'id') {
            throw new InvalidValueException('Cannot load elements using key "id" with keyfield '.implode("|", (array) static::getKeyField()));
        }

        /** @var SearchManager $search */
        $search = \App\App::getInstance()->getSearch();
        foreach($conditions as $condition) {
            $search->addAndCondition($condition);
        }

        $type = strtolower(static::getClassBasename(static::class));
        $search->addAndCondition('type', $type);

        $count_result = $search->countAll();
        for ($page = 0; $page < intval($count_result / SearchManager::MAX_ELEMENTS_PER_QUERY)+1; $page++) {
            $result = $search->searchData($page, SearchManager::MAX_ELEMENTS_PER_QUERY);
            $ids = array_map(function($el) {
                return $el['id'];
            }, $result['docs']);
        }

        return static::getCollection()->where(['id' => $ids]);
    }

    /**
     * basic select statement
     *
     * @param array $options
     * @return PDOStatement
     */
    public static function select(array $options = []): PDOStatement
    {
        return App::getInstance()->getDb()->select(static::defaultTableName(), $options);
    }

    /**
     * gets basic where statement for model
     *
    * @param array|null $condition
     * @param array $order
     * @return Result
     */
    protected static function getModelBasicWhere(?array $condition = [], array $order = []): Result
    {
        if ($condition == null) {
            $condition = [];
        }

        $conditions_where = [];
        $conditions_wherenot = [];

        if (!is_array($condition)) {
             $condition = [$condition];
        }

        /** @var Result $stmt */
        $stmt = App::getInstance()->getDb()->table(
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
            $stmt = $stmt->orderBy(static::getKeyField());
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

    public static Function getKeyField() : string|array
    {
        return static::$keyField;
    }

    protected static function loadedObjectsIdentifier(mixed $id) : string
    {
        $keyField = static::getKeyField();

        if (is_array($keyField)) {
            return implode("|", array_map(function($column, $value) {
                return $column.':'.$value;
            }, $keyField, $id));
        }

        // $id can't be an array here
        if (is_array($id) && isset($id[$keyField])) {
            $id = $id[$keyField];
        } else if (is_array($id)) {
            $id = reset($id);
        }

        return $keyField.':'.$id;
    }

    public function getKeyFieldValue() : mixed
    {
        if (!is_array(static::getKeyField())) {
            return $this->getData(static::getKeyField());
        }

        return array_combine(static::getKeyField(), array_map(function($column) {
            return $this->getData($column);
        }, static::getKeyField()));
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
     * @param mixed $id
     * @param bool $reset
     * @return self
     */
    public static function load(mixed $id, bool $reset = false): BaseModel
    {
        /** @var DebugBar $debugbar */
        $debugbar = App::getInstance()->getDebugbar();

        $measure_key = 'load model: ' . static::defaultTableName();

        if (App::getInstance()->getEnvironment()->canDebug()) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $keyField = static::getKeyField();
        if (is_array($keyField)) {
            $condition = array_combine($keyField, $id);
        } else {
            if (is_array($id) && isset($id[$keyField])) {
                $id = $id[$keyField];
            } else if (is_array($id)) {
                $id = reset($id);
            }

            $condition = [$keyField => $id];
        }

        if (isset(static::$loadedObjects[static::defaultTableName()][static::loadedObjectsIdentifier($id)]) && !$reset) {
            if (App::getInstance()->getEnvironment()->canDebug()) {
                $debugbar['time']->stopMeasure($measure_key);
            }
            return static::$loadedObjects[static::defaultTableName()][static::loadedObjectsIdentifier($id)];
        }

        $object = static::getCollection()->where($condition)->getFirst();
        if (!$object) {
            throw new ExceptionsNotFoundException();
        }

        if (App::getInstance()->getEnvironment()->canDebug()) {
            $debugbar['time']->stopMeasure($measure_key);
        }
        return $object;
    }

    /**
     * gets new empty model
     *
     * @param array $initial_data
     * @return static
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function new(array $initial_data = []): BaseModel
    {
        $db_row = App::getInstance()->getDb()->createRow(static::defaultTableName());
        $db_row->setData($initial_data);
        return new static($db_row);
    }

    /**
     * loads model by field - value pair
     *
     * @param string $field
     * @param mixed $value
     * @return self
     * @throws BasicException
     * @throws DependencyException
     * @throws ExceptionsNotFoundException
     */
    public static function loadBy(string $field, mixed $value): BaseModel
    {
        $item = static::getCollection()->where([$field => $value])->getFirst();

        if (!$item) {
            throw new ExceptionsNotFoundException();
        }

        return $item;
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
     * {@inheritdoc}
     * @param mixed $key
     * @return mixed
     */
    public function __get(mixed $key): mixed
    {
        return $this->db_row[$key];
    }

    /**
     * {@inheritdoc}
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function __set(mixed $key, mixed $value): void
    {
        $this->db_row[$key] = $value;
    }

    /**
     * {@inheritdoc}
     * @param mixed $name
     * @return bool
     */
    public function __isset(mixed $name): bool
    {
        return isset($this->db_row[$name]);
    }

    /**
     * {@inheritdoc}
     * @param mixed $name
     * @return void
     */
    public function __unset(mixed $name): void
    {
        unset($this->db_row[$name]);
    }

    /**
     * {@inheritdoc}
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

        if ($name != 'getData' && $name != 'setData') {
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
            if (str_starts_with($method_name, 'load_by_')) {
                return $this->loadBy(substr($method_name, 8), ...$arguments);
            }
        }

        return call_user_func_array([$this->getDbRow(), $name], $arguments);
    }

    /**
     * gets model's data
     *
     * @param mixed|null $column
     * @return mixed
     */
    public function getData(mixed $column = null): mixed
    {
        $data = $this->getDbRow()->getData();

        if ($column != null) {
            return $data[$column] ?? null;
        }

        return $data;
    }

    /**
     * sets model's data
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data) : static
    {
        $this->getDbRow()->setData($data);

        return $this;
    }

    /**
     * gets Model json rapresentation
     * 
     * @param int $level internally used
     * @return string
     */
    public function toJson(int $level = 0)
    {
        $out = [];
        foreach ($this->getData() as $key => $value) {
            if ($value instanceof BaseModel) {
                $out[$key] = $value->toJson($level++);
            } else if(is_array($value)) {
                foreach ($value as &$v) {
                    if ($v instanceof BaseModel) {
                        $v = $v->toJson($level++);
                    }
                }
            } else {
                $out[$key] = $value;
            }
        }

        if ($level == 0) {
            return json_encode($out);
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable|\ArrayIterator
    {
        return $this->getDbRow()->getIterator();
    }

    /**
     * {@inheritdoc}
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     * {@inheritdoc}
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * {@inheritdoc}
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }

    /**
     * {@inheritdoc}
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->getDbRow()->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->getDbRow()->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->getDbRow()->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->getDbRow()->rewind();
    }

    /**
     * {@inheritdoc}
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
    public function persist(bool $recursive = true): BaseModel
    {
        /** @var DebugBar $debugbar */
        $debugbar = App::getInstance()->getDebugbar();

        $measure_key = 'persist model: ' . static::defaultTableName();

        if (App::getInstance()->getEnvironment()->canDebug()) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $this->prePersist();

        if (!$this->getDbRow()->exists() && in_array('created_at', static::getTableColumns()) && $this->getData('created_at') == null) {
            $this->getDbRow()->created_at = date("Y-m-d H:i:s", time());
        }
        if (in_array('updated_at', static::getTableColumns())) {
            $this->getDbRow()->updated_at = date("Y-m-d H:i:s", time());
        }

        $this->getDbRow()->update($this->getData(), $recursive);

        $this->postPersist();

        $this->setIsFirstSave(false);

        $this->original_data = $this->getDbRow()->getData();

        if (App::getInstance()->getEnvironment()->canDebug()) {
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
        if (in_array('created_at', static::getTableColumns()) && $this->getData('created_at') == null) {
            $this->getDbRow()->created_at = date("Y-m-d H:i:s", time());
        }
        if (in_array('updated_at', static::getTableColumns())) {
            $this->getDbRow()->updated_at = date("Y-m-d H:i:s", time());
        }
        $this->emitEvent('pre_persist');
        return $this;
    }

    /**
     * post persist hook
     *
     * @return self
     */
    public function postPersist(): BaseModel
    {
        $this->emitEvent('post_persist');

        if (App::getInstance()->getEnvironment()->getVariable('ENABLE_VERSIONING', false) && 
            static::class != ModelVersion::class && 
            !is_subclass_of($this, ModelVersion::class) && 
            static::canSaveVersions() == true
        ) {
            $this->saveVersion();
        }

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
        /** @var DebugBar $debugbar */
        $debugbar = App::getInstance()->getDebugbar();

        $measure_key = 'delete model: ' . static::defaultTableName();

        if (App::getInstance()->getEnvironment()->canDebug()) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $this->preRemove();

        $this->getDbRow()->delete();

        $this->postRemove();

        if (App::getInstance()->getEnvironment()->canDebug()) {
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
        $this->emitEvent('pre_remove');
        return $this;
    }

    /**
     * post remove hook
     *
     * @return self
     */
    public function postRemove(): BaseModel
    {
        $this->emitEvent('post_remove');
        return $this;
    }

    /**
     * sets is first save flag
     *
     * @param bool $is_first_save
     * @return $this
     */
    public function setIsFirstSave(bool $is_first_save): BaseModel
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
     * @param mixed|null $key
     * @return mixed
     */
    protected function getOriginalData(mixed $key = null): mixed
    {
        if ($key != null && array_key_exists($key, $this->original_data)) {
            return $this->original_data[$key];
        }

        return $this->original_data;
    }

    /**
     * @return Row database row
     */
    protected function getDbRow(): ?Row
    {
        return $this->db_row;
    }

    /**
     * @param Row database row $db_row
     *
     * @return self
     */
    protected function setDbRow(Row $db_row): BaseModel
    {
        $this->db_row = $db_row;

        return $this;
    }

    /**
     * @param string table name $table_name
     *
     * @return self
     */
    public function setTableName(string $table_name): BaseModel
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

    /**
     * returns table columns
     * 
     * @return array
     */
    public static function getTableColumns() : array
    {
        return array_values(array_map(fn($column) => $column->getName(), App::getInstance()->getSchema()->getTable(static::defaultTableName())?->getColumns()));
    }

    /**
     * Emits event to event manager
     * 
     * @return void
     */
    protected function emitEvent($eventName, array $additionalData = []) : void
    {
        // ensure event name is lowercase
        $eventName = trim(strtolower($eventName));

        // emit event post persist
        App::getInstance()->event('model_'.$eventName, ['object' => $this] + $additionalData);
        App::getInstance()->event(strtolower(static::getClassBasename($this)) . '_' . $eventName, ['object' => $this] + $additionalData);
    }

    /**
     * Determines if model can be duplicated
     * 
     * @return bool
     */
    public static function canBeDuplicated() : bool
    {
        return true;
    }

    /**
     * Duplicate Model
     * 
     * @return BaseModel
     */
    public function duplicate() : BaseModel
    {
        if (!static::canBeDuplicated()) {
            throw new RuntimeException("Model of class " . basename(str_replace("\\", "/", get_class($this))) . " can't be duplicated.");
        }

        $data = $this->getData();
        $keyfield = static::getKeyField();

        // unset key field(s)
        if (is_array($keyfield)) {
            foreach ($keyfield as $key) {
                unset($data[$key]);
            }
        } else {
            unset($data[$keyfield]);
        }

        // any unique constraint field should be unset too
        $tableInfo = App::getInstance()->getSchema()->getTable(static::defaultTableName());
        foreach ($tableInfo->getIndexes() as $index) {
            if ($index->getType() == 'UNIQUE') {
                foreach ($index->getColumns() as $column) {
                    /** @var IndexColumn $column */
                    unset($data[$column->getName()]);
                }
            }
        }

        if (in_array('created_at', static::getTableColumns())) {
            unset($data['created_at']);
        }
        if (in_array('updated_at', static::getTableColumns())) {
            unset($data['updated_at']);
        }

        if (isset($data['title'])) {
            $data['title'] .= ' Copy';
        }

        // create new object with same data
        $out = static::new($data);

        return $out;
    }

    public static function serializeForVersioning(
        BaseModel $model,
        array &$visited = [],
        int $depth = 0,
        int $maxDepth = 1 // default 1 level, -1 = no limits
    ): array
    {
        $visited['obj'] ??= [];
        $visited['key'] ??= [];

        $className = get_class($model);
        $primaryKeyValue = $model->getKeyFieldValue();
        $uniqueKey = $className . ':' . json_encode($primaryKeyValue);

        $objectId = spl_object_id($model);
        if (isset($visited['obj'][$objectId])) {
            return [
                '__class' => $className, 
                '__primaryKey' => $primaryKeyValue,
                '__reference' => "object#$objectId", 
            ];
        }

        if ($maxDepth >= 0 && $depth > $maxDepth) {
            return [
                '__class' => $className, 
                '__primaryKey' => $primaryKeyValue,
                '__maxDepthReached' => true,
            ];
        }

        $visited['obj'][$objectId] = $uniqueKey;

        if (isset($visited['key'][$uniqueKey])) {
            return [
                '__class' => $className, 
                '__primaryKey' => $primaryKeyValue,
                '__reference' => "key#$uniqueKey"
            ];
        }
        $visited['key'][$uniqueKey] = true;

        $data = [
            '__class' => $className,
            '__primaryKey' => $primaryKeyValue,
            '__objectId' => $objectId,
            '__data' => $model->getData(),
        ];

        $reflection = new ReflectionClass($className);
        $methods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn($m) => str_starts_with($m->getName(), 'get')
                && $m->getDeclaringClass()->getName() === $className
                && $m->getNumberOfRequiredParameters() === 0
        );

        foreach ($methods as $method) {
            $name = lcfirst(substr($method->getName(), 3));

            try {
                $value = $method->invoke($model);
            } catch (Throwable $e) {
                continue;
            }

            if ($value instanceof BaseModel) {
                $data[$name] = static::serializeForVersioning($value, $visited, $depth + 1, $maxDepth);
            } elseif (is_array($value)) {
                $data[$name] = [];
                foreach ($value as $key => $item) {
                    if ($item instanceof BaseModel) {
                        $data[$name][$key] = static::serializeForVersioning($item, $visited, $depth + 1, $maxDepth);
                    } elseif (is_object($item)) {
                        if (method_exists($item, 'toArray')) {
                            $data[$name][$key] = $item->toArray();
                        } elseif ($item instanceof \JsonSerializable) {
                            $data[$name][$key] = $item->jsonSerialize();
                        } else {
                            $data[$name][$key] = get_object_vars($item);
                        }
                    } else {
                        $data[$name][$key] = $item;
                    }
                }
            } elseif (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $data[$name] = $value->toArray();
                } elseif ($value instanceof \JsonSerializable) {
                    $data[$name] = $value->jsonSerialize();
                } else {
                    $data[$name] = get_object_vars($value);
                }
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }


    /**
     * determines if varsion data should be saved on postPersist hook
     * 
     * @return bool
     */
    public Function canSaveVersions() : bool
    {
        return false;
    }

    /**
     * creates a version of current model
     */
    public function saveVersion() : ModelVersion
    {
        if (!App::getInstance()->getEnvironment()->getVariable('ENABLE_VERSIONING', false)) {
            throw new RuntimeException("Versioning is disabled");
        }

        /** @var ModelVersion $version */
        $version = App::getInstance()->containerMake(ModelVersion::class);

        $serialization = static::serializeForVersioning($this);

        $version
            ->setClassName($serialization['__class'])
            ->setPrimaryKey($serialization['__primaryKey'])
            ->setVersionData(json_encode($serialization));

        if ($user = App::getInstance()->getAuth()->getCurrentUser()) {
            if (!$user?->getId() || ($user instanceof GuestUser)) {
                $version->setUserId(null);
            } else {
                $version->setUserId($user->getId());
            }
        }

        return $version->persist();
    }

    /**
     * Restore a previous version of the model
     *
     * @param int|string $version_id
     * @param bool $deep
     * @return static
     * @throws \Exception
     */
    public function restoreVersion($version_id, bool $deep = true): static
    {
        $version = $this->getVersionById($version_id);
        if (!$version) {
            throw new \Exception("Version {$version_id} not found");
        }

        $data = json_decode($version->getData(), true);
        if (empty($data) || !isset($data['__data'])) {
            throw new \Exception("Invalid version data for {$version_id}");
        }

        $this->emitEvent('pre_restore', ['version' => $version]);

        $restoreData = $data['__data'] ?? [];

        if ($deep) {
            foreach ($restoreData as $k => &$v) {
                if (is_array($v) && isset($v['__class']) && is_subclass_of($v['__class'], BaseModel::class)) {
                    $class = $v['__class'];
                    try {
                        $related = null;
                        $pkField = (new $class())->getKeyFieldName();
                        if (isset($v['__data'][$pkField])) {
                            $related = $class::load($v['__data'][$pkField]);
                        }
                        if (empty($related) && !empty($v['__data'])) {
                            $related = App::getInstance()->containerMake($class);
                            $related->setData($v['__data'])->persist();
                        }
                        if ($related) {
                            $v = $related;
                        } else {
                            unset($restoreData[$k]);
                        }
                    } catch (\Throwable $e) {
                        App::getInstance()->getLog()->warning("Deep restore failed for {$k}: " . $e->getMessage());
                        unset($restoreData[$k]);
                    }
                }

                if (is_array($v) && !empty($v)) {
                    $first = reset($v);
                    if (is_array($first) && isset($first['__class']) && is_subclass_of($first['__class'], BaseModel::class)) {
                        $newArr = [];
                        foreach ($v as $idx => $item) {
                            if (!is_array($item) || !isset($item['__class'])) {
                                continue;
                            }
                            $class = $item['__class'];
                            try {
                                $related = null;
                                $pkField = (new $class())->getKeyFieldName();
                                if (isset($item['__data'][$pkField])) {
                                    $related = $class::load($item['__data'][$pkField]);
                                }
                                if (empty($related) && !empty($item['__data'])) {
                                    $related = App::getInstance()->containerMake($class);
                                    $related->setData($item['__data'])->persist();
                                }
                                if ($related) {
                                    $newArr[] = $related;
                                }
                            } catch (\Throwable $e) {
                                App::getInstance()->getLog()->warning("Deep restore array element failed for {$k}#{$idx}: " . $e->getMessage());
                            }
                        }
                        $v = $newArr;
                    }
                }
            }
            unset($v);
        } else {
            $restoreData = array_filter(
                $restoreData,
                fn($v) => is_scalar($v) || is_null($v),
            );
        }

        $scalarData = [];
        $structuredData = [];

        foreach ($restoreData as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $scalarData[$key] = $value;
            } else {
                $structuredData[$key] = $value;
            }
        }

        if (!empty($scalarData)) {
            try {
                $this->setData($scalarData);
            } catch (\Throwable $e) {
                App::getInstance()->getLog()->warning("restore:setData failed: " . $e->getMessage());
            }
        }

        if ($deep) {
            foreach ($structuredData as $key => $value) {
                $this->applyRestoredField($key, $value);
            }
        }

        $this->persist();

        $this->emitEvent('post_restore', ['version' => $version]);

        return $this;
    }

    /**
     * Apply a restored field to the current model
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function applyRestoredField(string $key, mixed $value): void
    {
        $utils = App::getInstance()->getUtils();
        $log = App::getInstance()->getLog();

        $pascal = static::snakeCaseToPascalCase($key);
        $setter = 'set' . $pascal;
        $getter = 'get' . $pascal;

        // usa la tua funzione di singolarizzazione
        $singular = $utils->singularize($key);
        $singularPascal = static::snakeCaseToPascalCase($singular);
        $adder = 'add' . $singularPascal;
        $remover = 'remove' . $singularPascal;

        // === 1) Valore singolo BaseModel ===
        if ($value instanceof BaseModel) {
            if (method_exists($this, $setter)) {
                try {
                    $this->$setter($value);
                    return;
                } catch (\Throwable $e) {
                    $log->warning("restore:setter failed for {$key}: " . $e->getMessage());
                }
            }

            try {
                $this->setData([$key => $value]);
            } catch (\Throwable $e) {
                $log->warning("restore:assign failed for {$key}: " . $e->getMessage());
            }
            return;
        }

        // === 2) Array (relazione multipla o lista di scalari) ===
        if (is_array($value)) {
            // se esiste setXXX, usalo (anche per array vuoti)
            if (method_exists($this, $setter)) {
                try {
                    $this->$setter($value);
                    return;
                } catch (\Throwable $e) {
                    $log->warning("restore:setter(array) failed for {$key}: " . $e->getMessage());
                }
            }

            // se esiste addXXX, prova a svuotare e riaggiungere
            if (method_exists($this, $adder)) {
                // svuotamento tramite setter, getter/remover o proprietà diretta
                if (method_exists($this, $setter)) {
                    try {
                        $this->$setter([]);
                    } catch (\Throwable $e) {
                        $log->info("restore: couldn't clear via setter for {$key}: " . $e->getMessage());
                    }
                } elseif (method_exists($this, $getter) && method_exists($this, $remover)) {
                    try {
                        $current = $this->$getter();
                        if (is_iterable($current)) {
                            foreach ($current as $item) {
                                try {
                                    $this->$remover($item);
                                } catch (\Throwable) {
                                    // ignora singoli errori
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        $log->info("restore: couldn't clear via getter/remover for {$key}: " . $e->getMessage());
                    }
                } else {
                    try {
                        $this->setData([$key => []]);
                    } catch (\Throwable $e) {
                        $log->info("restore: couldn't clear property {$key}: " . $e->getMessage());
                    }
                }

                // aggiungi i nuovi elementi
                foreach ($value as $item) {
                    try {
                        $this->$adder($item);
                    } catch (\Throwable $e) {
                        $log->warning("restore:add failed for {$key}: " . $e->getMessage());
                    }
                }

                return;
            }

            // fallback: assegna la proprietà direttamente
            try {
                $this->setData([$key => $value]);
            } catch (\Throwable $e) {
                $log->info("restore: skipped array for {$key}: " . $e->getMessage());
            }

            return;
        }

        // === 3) Tipo non supportato ===
        $log->info("restore: ignored field {$key} (unsupported type)");
    }


    /**
     * get the collection of version available for current model
     */
    public function getVersions() : BaseCollection
    {
        if (!App::getInstance()->getEnvironment()->getVariable('ENABLE_VERSIONING', false)) {
            throw new RuntimeException("Versioning is disabled");
        }

        return ModelVersion::getCollection()->where([
            'class_name' => static::class, 
            'primary_key' => is_array($this->getKeyFieldValue()) ? json_encode($this->getKeyFieldValue()) : $this->getKeyFieldValue()
        ])->addOrder(['created_at' => 'DESC']);
    }
}
 