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

use \LessQL\Row;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Base\Exceptions\InvalidValueException;
use \Exception;

/**
 * A wrapper for LessQL Row
 */
abstract class Model extends ContainerAwareObject implements \ArrayAccess, \IteratorAggregate
{
    const ITEMS_PER_PAGE = 50;

    /** @var Row database row */
    protected $dbrow;

    /** @var string table name */
    public $tablename;

    /**
     * {@inheritdocs}
     * @param ContainerInterface $container
     * @param Row|null           $dbrow
     */
    public function __construct(ContainerInterface $container, $dbrow = null)
    {
        parent::__construct($container);

        $name = $this->getTableName();
        if ($dbrow instanceof Row) {
            $this->checkDbName($dbrow);
        } else {
            $dbrow = $this->getDb()->createRow($name);
        }
        $this->tablename = $name;
        $this->dbrow = $dbrow;
    }

    /**
     * gets object model name
     * @return string
     */
    protected function getModelName()
    {
        return basename(str_replace("\\", "/", get_called_class()));
    }

    /**
     * gets table name
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
     * @param  Row    $dbrow
     * @return self
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
     * gets basic where statement for model
     * @param  ContainerInterface $container
     * @param  array              $condition
     * @param  array              $order
     * @return \LessQL\Result
     */
    protected static function getModelBasicWhere(ContainerInterface $container, $condition = [], $order = [])
    {
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
     * @param  ContainerInterface $container
     * @param  array              $condition
     * @param  array              $order
     * @return array
     */
    public static function all(ContainerInterface $container, $condition = [], $order = [])
    {
        return array_map(function ($el) use ($container) {
            return $container->make(static::class, ['dbrow' => $el]);
        },
        static::getModelBasicWhere($container, $condition, $order)->fetchAll());
    }

    /**
     * return subset of found items (useful for paginate)
     * @param  ContainerInterface $container
     * @param  Request|null       $request
     * @param  integet            $page_size
     * @param  array              $condition
     * @param  array              $order
     * @return array
     */
    public static function paginate(ContainerInterface $container, Request $request = null, $page_size = self::ITEMS_PER_PAGE, $condition = [], $order = [])
    {
        if ($request == null) {
            $request = Request::createFromGlobals();
        }

        $page = $request->get('page') ?? 0;
        $start = (int)$page * $page_size;
        $items = array_map(function ($el) use ($container) {
            return $container->make(static::class, ['dbrow' => $el]);
        },
        static::getModelBasicWhere($container, $condition, $order)->limit($page_size, $start)->fetchAll());

        $total = $container->get('db')->table(
            static::defaultTableName()
        )->where($condition, $order)->count();

        return ['items' => $items, 'page' => $page, 'total' => $total];
    }

    /**
     * finds elements
     * @param  ContainerInterface $container
     * @param  array|string       $condition
     * @param  array              $order
     * @return \LessQL\Result
     */
    public static function where(ContainerInterface $container, $condition, $order = [])
    {
        return array_map(function ($el) use ($container) {
            return $container->make(static::class, ['dbrow' => $el]);
        },
        static::getModelBasicWhere($container, $condition, $order)->fetchAll());
    }

    /**
     * gets model table name
     * @return string
     */
    public static function defaultTableName()
    {
        $path = explode('\\', static::class);
        return strtolower(static::pascalCaseToSnakeCase(array_pop($path)));
    }

    /**
     * fills empty model with data
     * @param  integer $id
     * @return self
     */
    public function fill($id)
    {
        if ($id instanceof Row) {
            $this->checkDbName($id);
            $this->dbrow = $id;
            $this->tablename = $this->dbrow->getTable();
        } elseif (is_numeric($id)) {
            $this->tablename = $this->getTableName();
            $dbrow = $this->getDb()->table(static::defaultTableName(), $id);
            $this->dbrow = $dbrow;
        }

        return $this;
    }

    /**
     * checks if model is loaded
     * @return boolean
     */
    public function isLoaded()
    {
        return ($this->dbrow instanceof Row) && $this->dbrow->exists();
    }

    /**
     * ensures model is loaded
     * @return self
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
     * @return self
     */
    public function reset()
    {
        if ($this->dbrow->exists()) {
            $dbrow = $this->getDb()->table($this->dbrow->getTable(), $this->dbrow->getOriginalId());
            if ($dbrow) {
                $this->dbrow = $dbrow;
            }
        }

        return $this;
    }

    /**
     * loads model by id
     * @param  ContainerInterface $container
     * @param  integeer           $id
     * @return self
     */
    public static function load(ContainerInterface $container, $id)
    {
        $dbrow = $container->get('db')->table(static::defaultTableName(), $id);
        return new static($container, $dbrow);
    }

    /**
     * gets new empty model
     * @param  ContainerInterface $container
     * @return self
     */
    public static function new(ContainerInterface $container)
    {
        $dbrow = $container->get('db')->createRow(static::defaultTableName());
        return new static($container, $dbrow);
    }

    /**
     * loads model by field - value pair
     * @param  ContainerInterface $container
     * @param  string             $field
     * @param  string             $value
     * @return self
     */
    public static function loadBy(ContainerInterface $container, $field, $value)
    {
        $dbrow = $container->get('db')->table(static::defaultTableName())->where($field, $value)->limit(1)->fetch();
        return new static($container, $dbrow);
    }

    /**
     * {@inheritdocs}
     */
    public function __get($key)
    {
        return $this->dbrow[$key];
    }

    /**
     * {@inheritdocs}
     */
    public function __set($key, $value)
    {
        $this->dbrow[$key] = $value;
        return $this;
    }

    /**
     * {@inheritdocs}
     */
    public function __isset($name)
    {
        return isset($this->dbrow[$name]);
    }

    /**
     * {@inheritdocs}
     */
    public function __unset($name)
    {
        unset($this->dbrow[$name]);
    }

    /**
     * {@inheritdocs}
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

    /**
     * {@inheritdocs}
     */
    public function getIterator()
    {
        return $this->dbrow->getIterator();
    }

    /**
     * {@inheritdocs}
     */
    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    /**
     * {@inheritdocs}
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * {@inheritdocs}
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * {@inheritdocs}
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

        return $this;
    }

    /**
     * pre persist hook
     * @return self
     */
    public function prePersist()
    {
        return $this;
    }

    /**
     * post persist hook
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
     * @return self
     */
    public function preRemove()
    {
        return $this;
    }

    /**
     * post remove hook
     * @return self
     */
    public function postRemove()
    {
        return $this;
    }
}
