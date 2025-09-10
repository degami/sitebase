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

namespace App\Base\Abstracts\Controllers;

use App\App;
use App\Base\Models\RequestLog;
use App\Base\Routing\RouteInfo;
use App\Base\Traits\FrontendPageTrait;
use App\Base\Models\Menu;
use App\Base\Models\Rewrite;
use App\Base\Models\Website;
use DebugBar\DebugBarException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use League\Plates\Template\Template;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Degami\Basics\Html\TagElement;
use App\Base\Exceptions\PermissionDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Base for frontend pages with language in route path
 */
abstract class FrontendPageWithLang extends FrontendPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): array
    {
        $routePath = str_replace("app/site/controllers/", "", str_replace("\\", "/", 
            str_replace("app/base/controllers/", "", str_replace("\\", "/", strtolower(static::class))),
        ));
        $routeKey = str_replace("/", ".", trim($routePath, "/"));
        $routePath = str_replace("frontend/","", $routePath);

        return [
            $routeKey => $routePath,
            $routeKey.'.withlang' => '{lang:[a-z]{2}}/' . ltrim($routePath, '/'),
        ];
    }

    protected function hasLang() : bool
    {
        return !empty($this->getRouteInfo()->getVar('lang'));
    }
}
