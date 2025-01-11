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

use Psr\Container\ContainerInterface;
use App\Base\Abstracts\ContainerAwareObject;
use Degami\Basics\Exceptions\BasicException;
use Redis as RedisClient;

/**
 * Redis Manager
 */
class Manager extends ContainerAwareObject
{
    protected ?RedisClient $client = null;
    protected bool $connected = false;

    public function __construct(
        protected ContainerInterface $container,

    ) {
        parent::__construct($container);

        $this->client = new RedisClient();
        $this->connected = $this->client->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'), 5);
        if (!$this->connected) {
            throw new BasicException("Redis client is not connected");
        }

        if (!empty(getenv('REDIS_PASSWORD', ''))) {
            $this->client->auth(getenv('REDIS_PASSWORD',''));
        }
        $this->client->select(getenv('REDIS_DATABASE'));
    }

    public function isConnected() : bool
    {
        return $this->connected;
    }

    public function getClient() : ?RedisClient
    {
        return $this->client;
    }

    public function __call(string $name, mixed $arguments) : mixed
    {
        return call_user_func_array([$this->client, $name], $arguments);
    }
}