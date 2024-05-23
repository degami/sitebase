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

namespace App\Base\Abstracts;

use App\Base\Exceptions\InvalidValueException;
use Psr\Container\ContainerInterface;
use App\Base\Traits\ToolsTrait;
use App\Base\Traits\ContainerAwareTrait;
use App\Base\Traits\TranslatorsTrait;

/**
 * Base for objects that are aware of Container
 */
abstract class ContainerAwareObject
{
    use ContainerAwareTrait;
    use ToolsTrait;
    use TranslatorsTrait;

    /**
     * constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        protected ContainerInterface $container
    ) { }

    /**
     * {@inheritdocs}
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     * @throws InvalidValueException
     */
    public function __call(string $name, mixed $arguments): mixed
    {
        $method = strtolower(substr(trim($name), 0, 3));
        $prop = self::pascalCaseToSnakeCase(substr(trim($name), 3));
        if ($method == 'get' && $this->getContainer()->has($prop)) {
            return $this->getContainer()->get($prop);
        }

        throw new InvalidValueException("Method \"{$name}\" not found in class\"" . get_class($this) . "\"!", 1);
    }
}
