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

use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

/**
 * Base for pages rendering a JSON response
 */
abstract class BaseJsonPage extends BasePage
{
    /**
     * {@inheritdoc}
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
        $this->response = $this->getContainer()->get(JsonResponse::class);
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response
    {
        try {
            return $this->getUtils()->createJsonResponse(
                array_merge(['success' => true,], $this->getJsonData())
            );
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e);
        }
    }

    /**
     * gets controller route name
     */
    static public function getPageRouteName() : string
    {
        $controllerClass = static::class;
        $path = str_replace("app/site/crud/", "", str_replace("\\", "/", strtolower($controllerClass)));
        $route_name = 'crud.' . str_replace("/", ".", trim($path, "/"));

        return $route_name;
    }

    /**
     * gets JSON data
     *
     * @return mixed
     */
    abstract protected function getJsonData(): mixed;
}
