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
     * @param Request|null $request
     * @throws BasicException
     */
    public function __construct(ContainerInterface $container, Request $request)
    {
        parent::__construct($container, $request);
        $this->response = $this->getContainer()->get(Response::class);
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
    public function process(RouteInfo $route_info = null, $route_data = [])
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
    abstract protected function getXMLData();
}
