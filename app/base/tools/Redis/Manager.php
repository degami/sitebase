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

namespace App\Base\Tools\Redis;

use App\Base\Abstracts\ContainerAwareObject;
use Degami\Basics\Exceptions\BasicException;
use Redis as RedisClient;

/**
 * Redis Manager
 */
class Manager extends ContainerAwareObject
{
    public const REDIS_TIMEOUT = 5;

    protected ?RedisClient $client = null;
    protected bool $connected = false;

    public function getClient() : RedisClient
    {
        if (is_null($this->client)) {
            $this->client = new RedisClient();
            $this->connected = $this->client->connect(
                $this->getEnv('REDIS_HOST', '127.0.0.1'), 
                $this->getEnv('REDIS_PORT', 6379), 
                self::REDIS_TIMEOUT
            );
    
            if (!$this->connected) {
                throw new BasicException("Redis client is not connected");
            }
    
            if (!empty($this->getEnv('REDIS_PASSWORD', ''))) {
                $this->client->auth($this->getEnv('REDIS_PASSWORD',''));
            }
            $this->client->select($this->getEnv('REDIS_DATABASE', 0));
        }

        return $this->client;
    }

    public function isEnabled() : bool
    {
        return $this->getEnv('REDIS_CACHE', 0) != 0;
    }

    public function isConnected() : bool
    {
        return $this->connected;
    }

    public function __call(string $name, mixed $arguments) : mixed
    {
        return call_user_func_array([$this->getClient(), $name], $arguments);
    }
}