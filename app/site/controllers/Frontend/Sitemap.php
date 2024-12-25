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

namespace App\Site\Controllers\Frontend;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Controllers\BaseXMLPage;
use App\Site\Models\Sitemap as SitemapModel;
use App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Base\Exceptions\NotFoundException;

/**
 * A Sitemap
 */
class Sitemap extends BaseXMLPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'sitemap/{id:\d+}';
    }

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws \DI\NotFoundException
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        $sitemap = null;

        if (isset($route_data['id'])) {
            $sitemap = $this->containerCall([SitemapModel::class, 'load'], ['id' => $route_data['id']]);
        }

        if (!($sitemap instanceof SitemapModel && $sitemap->isLoaded())) {
            throw new NotFoundException();
        }

        try {
            $response = $this
                ->getResponse()
                ->prepare($this->getRequest());

            $response
                ->setContent($sitemap->getContent())
                ->headers->set('Content-Type', 'text/xml');

            return $response;
        } catch (Exception $e) {
            return $this->getUtils()->exceptionXml($e, $this->getRequest());
        }
    }

    /**
     * {@inheritdoc}
     * dummy. is not used
     *
     * @return array
     */
    protected function getXMLData(): mixed
    {
        return [];
    }
}
