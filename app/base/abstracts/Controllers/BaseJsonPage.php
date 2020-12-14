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

namespace App\Base\Abstracts\Controllers;

use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Psr\Container\ContainerInterface;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \Exception;

/**
 * Base for pages rendering a JSON response
 */
abstract class BaseJsonPage extends BasePage
{
    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(ContainerInterface $container, Request $request, RouteInfo $route_info)
    {
        parent::__construct($container, $request, $route_info);
        $this->response = $this->getContainer()->get(JsonResponse::class);
    }

    /**
     * {@inheritdocs}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        try {
            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setData(array_merge(['success' => true,], $this->getJsonData()));
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e, $this->getRequest());
        }
    }

    /**
     * gets JSON data
     *
     * @return mixed
     */
    abstract protected function getJsonData();
}
