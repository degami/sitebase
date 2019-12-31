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
namespace App\Base\Tools\Cache;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\ContainerAwareObject;
use \Exception;
use \Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use \Phpfastcache\Exceptions\PhpfastcacheLogicException;
use \Phpfastcache\Exceptions\PhpfastcacheRootException;
use \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Psr\SimpleCache\CacheInterface;

/**
 * Cache Manager
 */
class Manager extends ContainerAwareObject implements CacheInterface
{
    const CACHE_TAG = 'sitebase_cache';

    /**
     * @var ExtendedCacheItemPoolInterface internal cache instance
     */
    protected $internalCacheInstance;

    /**
     * constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->internalCacheInstance = $this->getContainer()->get('cache_engine');
    }

    /**
     * retrieves element from cache
     *
     * @param  string $key
     * @param  null   $default
     * @return mixed|null
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function get($key, $default = null)
    {
        if ($this->getEnv('DISABLE_CACHE')) {
            return $default;
        }
        
        try {
            $cacheItem = $this->internalCacheInstance->getItem($key);
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
     * @param  int|\DateInterval $ttl
     * @return int|\Dateinterval
     */
    public function getCacheLifetime($ttl = null)
    {
        return (is_int($ttl) || $ttl instanceof \DateInterval) ? $ttl : ($this->getEnv('CACHE_LIFETIME') ?? 300);
    }

    /**
     * saves element in cache
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  null   $ttl
     * @return bool
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function set($key, $value, $ttl = null): bool
    {
        if ($this->getEnv('DISABLE_CACHE')) {
            return false;
        }

        $ttl = $this->getCacheLifetime($ttl);

        try {
            $cacheItem = $this->internalCacheInstance
                ->getItem($key)
                ->set($value)
                ->addTag(self::CACHE_TAG);
            if (\is_int($ttl) && $ttl <= 0) {
                $cacheItem->expiresAt((new \DateTime('@0')));
            } elseif (\is_int($ttl) || $ttl instanceof \DateInterval) {
                $cacheItem->expiresAfter($ttl);
            }
            return $this->internalCacheInstance->save($cacheItem);
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * deletes cached element
     *
     * @param  string $key
     * @return bool
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function delete($key): bool
    {
        try {
            return $this->internalCacheInstance->deleteItem($key);
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * clears cache
     *
     * @return bool
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function clear(): bool
    {
        try {
            return $this->internalCacheInstance->clear();
        } catch (PhpfastcacheRootException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * get multiple elements from cache
     *
     * @param  string[] $keys
     * @param  null     $default
     * @return \iterable
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof \Traversable) {
            $keys = \iterator_to_array($keys);
        }
        try {
            return array_map(
                function (ExtendedCacheItemInterface $item) {
                    return $item->get();
                },
                $this->internalCacheInstance->getItems($keys)
            );
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * sets multiple elements into cache
     *
     * @param  string[]               $values
     * @param  null|int|\DateInterval $ttl
     * @return bool
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $ttl = $this->getCacheLifetime($ttl);

        try {
            foreach ($values as $key => $value) {
                $cacheItem = $this->internalCacheInstance->getItem($key)->set($value);

                if (\is_int($ttl) && $ttl <= 0) {
                    $cacheItem->expiresAt((new \DateTime('@0')));
                } elseif (\is_int($ttl) || $ttl instanceof \DateInterval) {
                    $cacheItem->expiresAfter($ttl);
                }
                $this->internalCacheInstance->saveDeferred($cacheItem);
                unset($cacheItem);
            }
            return $this->internalCacheInstance->commit();
        } catch (PhpfastcacheInvalidArgumentException $e) {
            throw new PhpfastcacheSimpleCacheException($e->getMessage(), 0, $e);
        }
    }

    /**
     * deletes multiple elements from cache
     *
     * @param  string[] $keys
     * @return bool
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function deleteMultiple($keys): bool
    {
        try {
            if ($keys instanceof \Traversable) {
                return $this->internalCacheInstance->deleteItems(\iterator_to_array($keys));
            } elseif (is_array($keys)) {
                return $this->internalCacheInstance->deleteItems($keys);
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
     * @param  string $key
     * @return bool
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     */
    public function has($key): bool
    {
        if ($this->getEnv('DISABLE_CACHE')) {
            return false;
        }

        try {
            $cacheItem = $this->internalCacheInstance->getItem($key);
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
     * @return \Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface
     */
    public function getInternalCacheInstance(): ExtendedCacheItemPoolInterface
    {
        return $this->internalCacheInstance;
    }

    /**
     * get cache statistics
     *
     * @return mixed
     */
    public function getStats()
    {
        return $this->getInternalCacheInstance()->getStats();
    }

    /**
     * {@inheritdocs}
     *
     * @param  string $name
     * @param  mixed  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getInternalCacheInstance(), $name], $arguments);
    }

    /**
     * get items by tag
     *
     * @inheritdoc
     * @param      string $tagName
     * @return     array
     */
    public function getAllItemsByTag($tagName): array
    {
        return $this->getItemsByTag($tagName);
    }
}
