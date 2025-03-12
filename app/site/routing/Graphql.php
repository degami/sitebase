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

namespace App\Site\Routing;

use App\Base\Abstracts\Routing\BaseRouter;
use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Routing\RouteInfo;

/**
 * Graphql Router Class
 */
class Graphql extends BaseRouter
{
    public const ROUTER_TYPE = 'graphql';

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getEnv('GRAPHQL'));
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getHttpVerbs(): array
    {
        return ['POST'];
    }

    /**
     * gets routes
     *
     * @return array
     * @throws BasicException
     * @throws InvalidValueException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Exception
     */
    public function getRoutes(): array
    {
        if (empty($this->routes)) {
            $this->routes = $this->getCachedControllers();
            if (empty($this->routes)) {
                // collect routes

                $this->addRoute("/graphql", "grapql.entrypoint", "[/]", \App\Base\GraphQl\Entrypoint::class, self::CLASS_METHOD, $this->getHttpVerbs());
                $this->addRoute("/graphql", "grapql.entrypoint.locale", "/{lang:[a-z]{2}}[/]", \App\Base\GraphQl\Entrypoint::class, self::CLASS_METHOD, $this->getHttpVerbs());

                // cache controllers for faster access
                $this->setCachedControllers($this->routes);
            }
        }
        return $this->routes;
    }

    /**
     * returns a RouteInfo instance for current request
     *
     * @param string|null $http_method
     * @param string|null $request_uri
     * @param string|null $domain
     * @return RouteInfo
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getRequestInfo(?string $http_method = null, ?string $request_uri = null, ?string $domain = null): RouteInfo
    {
        // set request info type as crud
        return parent::getRequestInfo($http_method, $request_uri, $domain)->setType(self::ROUTER_TYPE);
    }
}
