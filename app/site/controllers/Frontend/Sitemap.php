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
namespace App\Site\Controllers\Frontend;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\Controllers\BaseXMLPage;
use \App\Base\Abstracts\Controllers\BasePage;
use \App\App;
use \App\Site\Models\Sitemap as SitemapModel;
use \App\Site\Models\Website;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;
use \Spatie\ArrayToXml\ArrayToXml;
use \Exception;

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
    public static function getRoutePath()
    {
        return 'sitemap/{id:\d+}';
    }

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return '';
    }

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $sitemap = null;

        if (isset($route_data['id'])) {
            $sitemap = $this->getContainer()->call([SitemapModel::class, 'load'], ['id' => $route_data['id']]);
        }

        if (!($sitemap instanceof SitemapModel && $sitemap->isLoaded())) {
            return $this->getUtils()->errorPage(404);
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
            return $this->getUtils()->exceptionXml($e);
        }
    }

    /**
     * {@inheritdocs}
     * dummy. is not used
     *
     * @return array
     */
    protected function getXMLData()
    {
        return [];
    }
}
