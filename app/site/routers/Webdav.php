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

namespace App\Site\Routers;

use App\Base\Abstracts\Routing\BaseRouter;
use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Routing\RouteInfo;
use App\Site\Webdav\SitebaseAuthBackend;
use App\Site\Webdav\MediaRootDirectory;
use App\Site\Webdav\MediaTree;
use Sabre\DAV\Server;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;

/**
 * Webdav Router Class
 */
class Webdav extends BaseRouter
{
    public const ROUTER_TYPE = 'webdav';
    public const CLASS_METHOD = '__invoke';

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getEnv('WEBDAV'));
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getHttpVerbs(): array
    {
        return [
            'GET', 'POST', 'PUT', 'DELETE',
            'OPTIONS', 'PROPFIND', 'PROPPATCH',
            'MKCOL', 'COPY', 'MOVE', 'LOCK', 'UNLOCK',
            'HEAD',
        ];
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

                $this->addRoute("/webdav", "webdav.entrypoint", "[/{path:.*}]", self::class, self::CLASS_METHOD, $this->getHttpVerbs());

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
        // set request info type as webdav
        return parent::getRequestInfo($http_method, $request_uri, $domain)->setType(self::ROUTER_TYPE);
    }

    public function __invoke()
    {
        $authBackend = new SitebaseAuthBackend($this->getApp());
        $rootDirectory = new MediaRootDirectory();
        $tree = new MediaTree($rootDirectory);

        $server = new Server($tree);
        $server->setBaseUri('/webdav');

        $authPlugin = new AuthPlugin($authBackend, 'Sitebase WebDAV');
        $server->addPlugin($authPlugin);

        $browser = new BrowserPlugin();
        $server->addPlugin($browser);

        $server->setLogger($this->getLog());
        $server->debugExceptions = true;

        $server->start();
        exit;
    }
}
