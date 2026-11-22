<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Crud;

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\BaseRestPage;
use App\Base\Exceptions\NotFoundException;
use App\Base\Models\Block as BlockModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Container\ContainerInterface;
use App\Base\Routing\RouteInfo;
use App\Base\Models\RequestLog;
use Exception;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Models\Rewrite;
use App\Base\Routers\Web;
use App\Base\Models\Website;
use Throwable;

/**
 * Uncachanble Blocks render REST endpoint
 */
class UncachableBlock extends BaseRestPage
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return BlockModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws Throwable
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response
    {
        if (empty($data = json_decode($this->getEnvironment()->getRequestInfo()->getRequest()->getContent(), true))) {
            throw new \Exception('Missing Data');
        }

        if (!isset($route_data['_noLog'])) {
            try {
                /** @var RequestLog $log */
                $log = $this->containerMake(RequestLog::class);
                $log->fillWithRequest($this->getEnvironment()->getRequestInfo()->getRequest(), $this);
                $log->setResponseCode(200);
                $log->persist();
            } catch (Exception $e) {
                $this->getUtils()->logException($e, "Can't write RequestLog", $this->getEnvironment()->getRequestInfo()->getRequest());
                if ($this->getEnvironment()->canDebug()) {
                    return $this->getUtils()->exceptionPage($e, $this->getEnvironment()->getRequestInfo()->getRouteInfo());
                }
            }    
        }

        if (!isset($data['block_id'])) {
            throw new NotFoundException("Element not found");
        }

        /** @var BlockModel $block */
        $block = $this->loadObject($data['block_id']);

        /** @var Web $webrouter */
        $webRouter = $this->getWebRouter();

        $uri = $this->buildRequestUriFromParsedUrl(parse_url($data['url']));

        $website = null;
        if ($this->getEnvironment()->isCliServer()) {
            $website = $this->containerCall([Website::class, 'load'], ['id' => getenv('website_id')]);
        }

        /** @var RouteInfo $routeInfo */
        $routeInfo = $this->containerCall(
            [$webRouter, 'getRouteInfo'],
            [
                'http_method' => 'GET',
                'request_uri' => $uri,
                'domain' => ($this->getEnvironment()->isCliServer()) ? $website->domain : $this->getSiteData()->currentServerName()
            ]
        );

        $handler = $routeInfo->getHandler();
        $vars = $routeInfo->getVars();

        // inject container into vars
        //$vars['container'] = $this->getContainer();

        // inject request object into vars
        //$vars['request'] = $this->getEnvironment()->getRequestInfo()->getRequest();

        // inject routeInfo
        $vars['route_info'] = $routeInfo;

        // add route collected data
        $vars['route_data'] = $routeInfo->getVars();

        // inject locale
        $vars['locale'] = $data['locale'];

        $routeInfo->setVars($vars);
        /** @var BasePage $current_page */
        $current_page = $this->containerMake(reset($handler));

        $current_page->setRouteInfo($routeInfo);

        switch ($this->getVerb()) {
            case 'POST':
                $data = [
                    'html' => $block->render($current_page),
                ];

                return $this->getUtils()->createJsonResponse($data);
        }

        throw new \Exception('Cannot Render block');
    }

    protected function buildRequestUriFromParsedUrl(array $parsedUrl) {
        $uri = '';
    
        if (isset($parsedUrl['path'])) {
            $uri .= $parsedUrl['path'];
        }
    
        if (isset($parsedUrl['query'])) {
            $uri .= '?' . $parsedUrl['query'];
        }
    
        if (isset($parsedUrl['fragment'])) {
            $uri .= '#' . $parsedUrl['fragment'];
        }
    
        return $uri;
    }
}