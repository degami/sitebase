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

use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Site\Routing\RouteInfo;
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
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->response = $this->getContainer()->get(JsonResponse::class);
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
        try {
            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setData(array_merge(['success' => true,], $this->getJsonData()));
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e);
        }
    }

    /**
     * gets JSON data
     *
     * @return mixed
     */
    abstract protected function getJsonData();
}
