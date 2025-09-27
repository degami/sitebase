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

namespace App\Base\Tools\Cache;

use DateInterval;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use App\Base\Abstracts\ContainerAwareObject;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\SimpleCache\CacheInterface;
use ReflectionException;
use Traversable;

use function is_int;
use function iterator_to_array;

/**
 * Cache Manager
 */
class Manager extends ContainerAwareObject implements CacheInterface
{
    public const CACHE_TAG = 'sitebase_cache';

    /**
     * @var ExtendedCacheItemPoolInterface|null internal cache instance
     */
    public static ?ExtendedCacheItemPoolInterface $internalCacheInstance = null;

    /**
     * constructor
     *
     * @param ContainerInterface $container
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws ReflectionException
     */
    public function __construct(
        protected ContainerInterface $container
    ) {
        parent::__construct($container);

        if (self::$internalCacheInstance == null) {
            if ($this->getRedis()->isEnabled()) {
                $config = new \Phpfastcache\Drivers\Redis\Config([
                    'host' => $this->getEnvironment()->getVariable('REDIS_HOST', '127.0.0.1'),
                    'port' => intval($this->getEnvironment()->getVariable('REDIS_PORT', 6379)),
                    'password' => $this->getEnvironment()->getVariable('REDIS_PASSWORD', ''),
                    'database' => intval($this->getEnvironment()->getVariable('REDIS_DATABASE', 0)),
                ]);
                $cache = \Phpfastcache\CacheManager::getInstance('Redis', $config);
            } else {
                $config = new \Phpfastcache\Drivers\Files\Config([
                    'path' => \App\App::getDir(\App\App::ROOT) . DS . 'var' . DS . 'cache',
                ]);
                $config->setSecurityKey(str_replace(" ", "_", strtolower(getenv('APPNAME'))));
                $cache = \Phpfastcache\CacheManager::getInstance('Files', $config);    
            }

            self::$internalCacheInstance = $cache;
        }
    }

    /**
     * retrieves element from cache
     *
     * @param mixed $key
     * @param mixed|null $default
     * @return mixed
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function get($key, $default = null) : mixed
    {
        if ($this->getEnvironment()->getVariable('DISABLE_CACHE')) {
            return $default;
        }

        try {
            $cacheItem = $this->getInternalCacheInstance()->getItem($key);
            if (!$cacheItem->isExpired() && $cacheItem->get() !== null) {
                return $cacheItem->get();
            }

            return $default;
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * gets cache lifetime
     *
     * @param int|DateInterval|null $ttl
     * @return int|Dateinterval
     * @throws BasicException
     */
    public function getCacheLifetime(int|DateInterval|null $ttl = null): DateInterval|int
    {
        return (is_int($ttl) || $ttl instanceof DateInterval) ? $ttl : ($this->getEnvironment()->getVariable('CACHE_LIFETIME') ?? 300);
    }

    /**
     * saves element in cache
     *
     * @param mixed $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws BasicException
     */
    public function set($key, $value, $ttl = null): bool
    {
        if ($this->getEnvironment()->getVariable('DISABLE_CACHE')) {
            return false;
        }

        $ttl = $this->getCacheLifetime($ttl);

        try {
            $cacheItem = $this->getInternalCacheInstance()
                ->getItem($key)
                ->set($value)
                ->addTag(self::CACHE_TAG);
            if (is_int($ttl) && $ttl <= 0) {
                $cacheItem->expiresAt((new DateTime('@0')));
            } elseif (is_int($ttl) || $ttl instanceof DateInterval) {
                $cacheItem->expiresAfter($ttl);
            }
            return $this->getInternalCacheInstance()->save($cacheItem);
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * deletes cached element
     *
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function delete($key): bool
    {
        try {
            return $this->getInternalCacheInstance()->deleteItem($key);
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * clears cache
     *
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     */
    public function clear(): bool
    {
        try {
            return $this->getInternalCacheInstance()->clear();
        } catch (PhpfastcacheRootException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * get multiple elements from cache
     *
     * @param string[] $keys
     * @param null $default
     * @return iterable
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getMultiple($keys, $default = null): iterable
    {
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys);
        }
        try {
            return array_map(
                function (ExtendedCacheItemInterface $item) {
                    return $item->get();
                },
                $this->getInternalCacheInstance()->getItems($keys)
            );
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * sets multiple elements into cache
     *
     * @param mixed $values
     * @param null $ttl
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws BasicException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $ttl = $this->getCacheLifetime($ttl);

        try {
            foreach ($values as $key => $value) {
                $cacheItem = $this->getInternalCacheInstance()->getItem($key)->set($value);

                if (is_int($ttl) && $ttl <= 0) {
                    $cacheItem->expiresAt((new DateTime('@0')));
                } elseif (is_int($ttl) || $ttl instanceof DateInterval) {
                    $cacheItem->expiresAfter($ttl);
                }
                $this->getInternalCacheInstance()->saveDeferred($cacheItem);
                unset($cacheItem);
            }
            return $this->getInternalCacheInstance()->commit();
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * deletes multiple elements from cache
     *
     * @param string[] $keys
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        try {
            if ($keys instanceof Traversable) {
                return $this->getInternalCacheInstance()->deleteItems(iterator_to_array($keys));
            } elseif (is_array($keys)) {
                return $this->getInternalCacheInstance()->deleteItems($keys);
            } else {
                throw new phpFastCacheInvalidArgumentException('$keys must be an array/Traversable instance.');
            }
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * checks if element is present
     *
     * @param mixed $key
     * @return bool
     * @throws PhpfastcacheSimpleCacheException
     * @throws BasicException
     */
    public function has($key): bool
    {
        if ($this->getEnvironment()->getVariable('DISABLE_CACHE')) {
            return false;
        }

        try {
            $cacheItem = $this->getInternalCacheInstance()->getItem($key);
            return $cacheItem->isHit() && !$cacheItem->isExpired();
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Extra methods that are not part of
     * psr16 specifications
     */

    /**
     * gets internal cache instance
     *
     * @return ExtendedCacheItemPoolInterface
     */
    public function getInternalCacheInstance(): ExtendedCacheItemPoolInterface
    {
        return self::$internalCacheInstance;
    }

    /**
     * get cache statistics
     *
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        return $this->getInternalCacheInstance()->getStats();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call(string $name, mixed $arguments): mixed
    {
        return call_user_func_array([$this->getInternalCacheInstance(), $name], $arguments);
    }

    /**
     * get items by tag
     *
     * @param string $tagName
     * @return     array
     */
    public function getAllItemsByTag(string $tagName): array
    {
        return $this->getItemsByTag($tagName);
    }
}
