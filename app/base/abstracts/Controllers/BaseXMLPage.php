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
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ArrayToXml\ArrayToXml;
use Exception;

/**
 * Base for pages rendering an XML response
 */
abstract class BaseXMLPage extends BasePage
{
    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(ContainerInterface $container, Request $request, RouteInfo $route_info)
    {
        parent::__construct($container, $request, $route_info);
        $this->response = $this->getContainer()->get(Response::class);
    }

    /**
     * {@inheritdocs}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        try {
            $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setContent(ArrayToXml::convert($this->getXMLData()))
                ->headers->set('Content-Type', 'text/xml');

            return $this->getResponse();
        } catch (Exception $e) {
            return $this->getUtils()->exceptionXml($e, $this->getRequest());
        }
    }

    /**
     * gets XML data
     *
     * @return mixed
     */
    abstract protected function getXMLData(): mixed;
}
