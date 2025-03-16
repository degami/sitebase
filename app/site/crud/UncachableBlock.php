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

namespace App\Site\Crud;

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\BaseRestPage;
use App\Base\Exceptions\NotFoundException;
use App\Site\Models\Block as BlockModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Container\ContainerInterface;
use App\Base\Routing\RouteInfo;
use App\Base\Models\RequestLog;
use Exception;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Models\Rewrite;
use App\Site\Routing\Web;
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
        if (empty($data = json_decode($this->getRequest()->getContent(), true))) {
            throw new \Exception('Missing Data');
        }

        if (!isset($route_data['_noLog'])) {
            try {
                /** @var RequestLog $log */
                $log = $this->containerMake(RequestLog::class);
                $log->fillWithRequest($this->getRequest(), $this);
                $log->setResponseCode(200);
                $log->persist();
            } catch (Exception $e) {
                $this->getUtils()->logException($e, "Can't write RequestLog", $this->getRequest());
                if ($this->getEnv('DEBUG')) {
                    return $this->getUtils()->exceptionPage($e, $this->getRequest(), $this->getRouteInfo());
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
        if (php_sapi_name() == 'cli-server') {
            $website = $this->containerCall([Website::class, 'load'], ['id' => getenv('website_id')]);
        }

        /** @var RouteInfo $routeInfo */
        $routeInfo = $this->containerCall(
            [$webRouter, 'getRequestInfo'],
            [
                'http_method' => 'GET',
                'request_uri' => $uri,
                'domain' => (php_sapi_name() == 'cli-server') ? $website->domain : $this->getSiteData()->currentServerName()
            ]
        );

        $handler = $routeInfo->getHandler();
        $vars = $routeInfo->getVars();

        // inject container into vars
        //$vars['container'] = $this->getContainer();

        // inject request object into vars
        //$vars['request'] = $this->getRequest();

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

                /** @var JsonResponse $response */
                $response = $this->getResponse();
                
                return $response
                    ->prepare($this->getRequest())
                    ->setData($data);
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