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

namespace App\Base\Routers;

use App\Base\Abstracts\Routing\BaseRouter;
use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Routing\RouteInfo;
use App\App;
use App\Base\Tools\Setup\Helper as SetupHelper;

/**
 * Setup Router Class
 */
class Setup extends BaseRouter
{
    public const ROUTER_TYPE = 'setup';
    public const CLASS_METHOD = '__invoke';

    protected ?string $php_bin = null;
    protected ?string $composer_bin = null;
    protected ?string $composer_dir = null;
    protected ?string $npm_bin = null;

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return !App::installDone();
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getHttpVerbs(): array
    {
        return [
            'GET', 'POST',
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

                $this->addRoute("/setup", "setup.entrypoint", "/", self::class, self::CLASS_METHOD, $this->getHttpVerbs());

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
        $setupHelper = new SetupHelper();
        chdir(App::getDir(App::ROOT));

        if (file_exists('.install_done')) {
            return $this->getUtils()->createHtmlResponse($setupHelper->errorPage('Installation already done.'));
        }

        if (isset($_GET['step'])) {
            switch ($_GET['step']) {
                case 0:
                    $this->getUtils()->createHtmlResponse($setupHelper->step0());
                    break;
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                case 10:
                case 11:
                case 12:
                    return $this->getUtils()->createJsonResponse($setupHelper->{'step'.$_GET['step']}());
                default:
                    return $this->getUtils()->createHtmlResponse($setupHelper->errorPage('Invalid Step!'));

            }
        } else {
            return $this->getUtils()->createHtmlResponse($setupHelper->step0());
        }
    }
}
