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

namespace App\Site\Cron\Tasks;

use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use App\Base\Abstracts\ContainerAwareObject;

/**
 * Cache manager cron
 */
class CacheManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '0 */2 * * *';

    /**
     * class constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    /**
     * flush cache method
     *
     * @return bool
     * @throws PhpfastcacheSimpleCacheException|BasicException
     */
    public function flush(): bool
    {
        return $this->getCache()->clear();
    }
}
