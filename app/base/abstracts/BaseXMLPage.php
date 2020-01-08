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
namespace App\Base\Abstracts;

use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;
use \Spatie\ArrayToXml\ArrayToXml;
use \Exception;

/**
 * Base for pages rendering an XML response
 */
abstract class BaseXMLPage extends BasePage
{
    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->response = $this->getContainer()->get(Response::class);
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
        $result = parent::process($route_info);
        if ($result instanceof Response) {
            return $result;
        }
        try {
            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setContent(ArrayToXml::convert($getXMLData))
                ->headers->set('Content-Type', 'text/xml');
        } catch (Exception $e) {
            return $this->getUtils()->exceptionXml($e);
        }
    }

    /**
     * gets XML data
     *
     * @return mixed
     */
    abstract protected function getXMLData();
}
