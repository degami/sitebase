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
    protected $dbrow;

    /**
     * @var string table name
     */
    public $tablename;

    /**
     * @var boolean first save flag
     */
    protected $is_first_save;

    /**
     * @var array|null original model data
     */
    private $original_data = null;

    private static $loadedObjects = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Row|null $dbrow
     * @throws InvalidValueException
     * @throws BasicException
     */
    public function __construct(ContainerInterface $container, $dbrow = null)
    {
        parent::__construct($container);

        $name = $this->getTableName();
        if ($dbrow instanceof Row) {
            $this->checkDbName($dbrow);
            $this->setOriginalData($dbrow->getData());
        } else {
            $dbrow = $this->getDb()->createRow($name);
            $this->setOriginalData(null);
        }
        $this->setTablename($name);
        $this->setDbrow($dbrow);
        $this->setIsFirstSave($this->isNew());
    }

    /**
     * gets object model name
     *
     * @return string
     */
    protected function getModelName()
    {
        return basename(str_replace("\\", "/", get_called_class()));
    }

    /**
     * gets table name
     *
     * @return string
     */
    protected function getTableName()
    {
        if (trim($this->tablename) != '') {
            return $this->tablename;
        }
        return static::defaultTableName();
    }

    /**
     * checks if Row object is from correct table
     *
     * @param Row $dbrow
     * @return self
     * @throws InvalidValueException
     */
    private function checkDbName(Row $dbrow)
    {
        $name = $this->getTableName();
        if ($name != $dbrow->getTable()) {
            throw new InvalidValueException('Invalid Row Resource');
        }
        return $this;
    }

    /**
     * basic select statement
     *
     * @param  ContainerInterface $container
     * @param  array              $options
     * @return PDOStatement
     */
    public static function select(ContainerInterface $container, $options = [])
    {
        return $container->get('db')->select(static::defaultTableName(), $options);
    }

    /**
     * gets basic where statement for model
     *
     * @param  ContainerInterface $container
     * @param  array              $condition
     * @param  array              $order
     * @return Result
     */
    protected static function getModelBasicWhere(ContainerInterface $container, $condition = [], $order = [])
    {
        if ($condition == null) {
            $condition = [];
        }
        $stmt = $container->get('db')->table(
            static::defaultTableName()
        )->where($condition);

        if (!empty($order) && is_array($order)) {
            foreach ($order as $column => $direction) {
                if (!in_array(strtoupper(trim($direction)), ['ASC','DESC'])) {
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
     * @param  ContainerInterface $container
     * @param  array              $condition
     * @param  array              $order
     * @return array
     */
    public static function all(ContainerInterface $container, $condition = [], $order = [])
    {
        return array_map(
            function ($el) use ($container) {
                return $container->make(static::class, ['dbrow' => $el]);
            },
            static::getModelBasicWhere($container, $condition, $order)->fetchAll()
        );
    }

    /**
     * return subset of found items (useful for paginate)
     *
     * @param  ContainerInterface $container
     * @param  Request|null       $request
     * @param  integer            $page_size
     * @param  array              $condition
     * @param  array              $order
     * @return array
     */
    public static function paginate(ContainerInterface $container, Request $request = null, $page_size = self::ITEMS_PER_PAGE, $condition = [], $order = [])
    {
        if ($request == null) {
            $request = Request::createFromGlobals();
        }

        if ($condition == null) {
            $condition = [];
        }

        $page = $request->get('page') ?? 0;
        $start = (int)$page * $page_size;
        $items = array_map(
            function ($el) use ($container) {
                return $container->make(static::class, ['dbrow' => $el]);
            },
            static::getModelBasicWhere($container, $condition, $order)->limit($page_size, $start)->fetchAll()
        );

        $total = static::getModelBasicWhere($container, $condition, $order)->count();
        return ['items' => $items, 'page' => $page, 'total' => $total];
    }

    /**
     * finds elements
     *
     * @param  ContainerInterface $container
     * @param  array|string       $condition
     * @param  array              $order
     * @return array
     */
    public static function where(ContainerInterface $container, $condition, $order = [])
    {
        return array_map(
            function ($el) use ($container) {
                return $container->make(static::class, ['dbrow' => $el]);
            },
            static::getModelBasicWhere($container, $condition, $order)->fetchAll()
        );
    }

    /**
     * gets model table name
     *
     * @return string
     */
    public static function defaultTableName()
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
    public function fill($id)
    {
        if ($id instanceof Row) {
            $this->checkDbName($id);
            $this->setDbrow($id);
            $this->setTablename($this->dbrow->getTable());
        } elseif (is_numeric($id)) {
            $this->setTablename($this->getTableName());
            $dbrow = $this->getDb()->table(static::defaultTableName(), $id);
            $this->setDbrow($dbrow);
        }
        $this->setIsFirstSave($this->isNew());

        return $this;
    }

    /**
     * checks if model is loaded
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return ($this->dbrow instanceof Row) && $this->dbrow->exists();
    }

    /**
     * checks if model is new
     *
     * @return boolean
     */
    public function isNew()
    {
        return !$this->isLoaded();
    }

    /**
     * ensures model is loaded
     *
     * @return self
     * @throws Exception
     */
    public function checkLoaded()
    {
        if (!$this->isLoaded()) {
            throw new Exception($this->getModelName()." is not loaded", 1);
        }

        return $this;
    }

    /**
     * resets model
     *
     * @return self
     * @throws BasicException
     */
    public function reset()
    {
        if ($this->dbrow->exists()) {
            $dbrow = $this->getDb()->table($this->dbrow->getTable(), $this->dbrow->getOriginalId());
            if ($dbrow) {
                $this->setDbrow($dbrow);
                $this->setOriginalData($dbrow->getData());
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
    public static function load(ContainerInterface $container, $id, $reset = false)
    {
        if (isset(static::$loadedObjects[static::defaultTableName()][$id]) && !$reset) {
            return static::$loadedObjects[static::defaultTableName()][$id];
        }

        $dbrow = $container->get('db')->table(static::defaultTableName(), $id);
        return static::$loadedObjects[static::defaultTableName()][$id] = new static($container, $dbrow);
    }

    /**
     * loads model by id
     *
     * @param ContainerInterface $container
     * @param array $ids
     * @param bool $reset
     * @return array
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function loadMultiple(ContainerInterface $container, $ids, $reset = false)
    {
        $ids = array_filter($ids, function($el){
            return is_numeric($el) && $el > 0;
        });

        return static::loadMultipleByCondition($container,  ['id' => $ids]);
    }

    /**
     * loads model by id
     *
     * @param ContainerInterface $container
     * @param array $ids
     * @param bool $reset
     * @return array
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function loadMultipleByCondition(ContainerInterface $container, $condition, $reset = false)
    {
        $ids = [];
        foreach($container->get('db')->table(static::defaultTableName())->where($condition)->fetchAll() as $dbrow) {
            $ids[] = $dbrow->id;
            /** @var Result $dbrow */
            if (!isset($loadedObjects[static::defaultTableName()][$dbrow->id]) || $reset) {
                static::$loadedObjects[static::defaultTableName()][$dbrow->id] = new static($container, $dbrow);
            }
        }

        return array_intersect_key(static::$loadedObjects[static::defaultTableName()], $ids);
    }


    /**
     * gets new empty model
     *
     * @param ContainerInterface $container
     * @param array $initialdata
     * @return static
     * @throws InvalidValueException
     * @throws BasicException
     */
    public static function new(ContainerInterface $container, $initialdata = [])
    {
        $dbrow = $container->get('db')->createRow(static::defaultTableName());
        $dbrow->setData($initialdata);
        return new static($container, $dbrow);
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
    public static function loadBy(ContainerInterface $container, $field, $value)
    {
        $dbrow = $container->get('db')->table(static::defaultTableName())->where($field, $value)->limit(1)->fetch();
        return new static($container, $dbrow);
    }

    /**
     * {@inheritdocs}
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->dbrow[$key];
    }

    /**
     * {@inheritdocs}
     * @param $key
     * @param $value
     * @return BaseModel
     */
    public function __set($key, $value)
    {
        $this->dbrow[$key] = $value;
        return $this;
    }

    /**
     * {@inheritdocs}
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->dbrow[$name]);
    }

    /**
     * {@inheritdocs}
     * @param $name
     */
    public function __unset($name)
    {
        unset($this->dbrow[$name]);
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
        if (!($this->dbrow instanceof Row)) {
            throw new Exception("No row loaded", 1);
        }

        if ($name != 'getData') {
            $method_name = static::pascalCaseToSnakeCase($name);
            if (in_array($method = strtolower(substr($method_name, 0, 4)), ['get_','has_','set_'])) {
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

        return call_user_func_array([$this->dbrow, $name], $arguments);
    }

    public function getData($column = null)
    {
        $data = $this->dbrow->getData();

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
        return $this->dbrow->getIterator();
    }

    /**
     * {@inheritdocs}
     * @param $offset
     * @param $value
     * @return BaseModel
     */
    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    /**
     * {@inheritdocs}
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset)
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
        return $this->dbrow->current();
    }

    /**
     * {@inheritdocs}
     */
    public function key()
    {
        return $this->dbrow->key();
    }

    /**
     * {@inheritdocs}
     */
    public function next()
    {
        $this->dbrow->next();
    }

    /**
     * {@inheritdocs}
     */
    public function rewind()
    {
        $this->dbrow->rewind();
    }

    /**
     * {@inheritdocs}
     */
    public function valid()
    {
        return $this->dbrow->valid();
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
    public function persist()
    {
        $this->prePersist();

        if (!$this->dbrow->exists() && array_key_exists('created_at', $this->dbrow->getData())) {
            $this->dbrow->created_at = date("Y-m-d H:i:s", time());
        }
        if (array_key_exists('updated_at', $this->dbrow->getData())) {
            $this->dbrow->updated_at = date("Y-m-d H:i:s", time());
        }
        $this->dbrow->update($this->getData());

        $this->postPersist();

        $this->setIsFirstSave(false);

        $this->original_data = $this->dbrow->getData();

        return $this;
    }

    /**
     * pre persist hook
     *
     * @return self
     */
    public function prePersist()
    {
        return $this;
    }

    /**
     * post persist hook
     *
     * @return self
     */
    public function postPersist()
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
    public function remove()
    {
        $this->preRemove();

        $this->dbrow->delete();

        $this->postRemove();

        return $this;
    }

    /**
     * pre remove hook
     *
     * @return self
     */
    public function preRemove()
    {
        return $this;
    }

    /**
     * post remove hook
     *
     * @return self
     */
    public function postRemove()
    {
        return $this;
    }

    public function setIsFirstSave($is_first_save)
    {
        $this->is_first_save = $is_first_save;
        return $this;
    }

    public function isFirstSave()
    {
        return ($this->is_first_save == true);
    }


    protected function setOriginalData($original_data)
    {
        $this->original_data = $original_data;
        return $this;
    }

    protected function getOriginalData($key = null)
    {
        if ($key != null && array_key_exists($key, $this->original_data)) {
            return $this->original_data[$key];
        }

        return $this->original_data;
    }

    /**
     * @return Row database row
     */
    public function getDbrow()
    {
        return $this->dbrow;
    }

    /**
     * @param Row database row $dbrow
     *
     * @return self
     */
    public function setDbrow($dbrow)
    {
        $this->dbrow = $dbrow;

        return $this;
    }

    /**
     * @param string table name $tablename
     *
     * @return self
     */
    public function setTablename($tablename)
    {
        $this->tablename = $tablename;

        return $this;
    }

    public function getChangedData()
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
