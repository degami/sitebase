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

namespace App\Base\Abstracts\Controllers;

use App\Base\Abstracts\Models\Webhook;
use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use App\Site\Models\RequestLog;
use App\Base\Traits\PageTrait;

/**
 * Base for pages rendering a Webhook response
 */
abstract class BaseWebhookPage extends BasePage
{
    use PageTrait;

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
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        try {
            if (!$this->isValidWebhookRequest()) {
                throw new InvalidValueException("Invalid request");
            }

            try {
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

            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setData(array_merge(
                    ['success' => true,], 
                    $this->processWebhook($this->getWebhookData())
                ));
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e, $this->getRequest());
        }
    }

    /**
     * gets controller route name
     */
    static public function getPageRouteName() : string
    {
        $controllerClass = static::class;
        $path = str_replace("app/site/webhooks/", "", str_replace("\\", "/", strtolower($controllerClass)));
        $route_name = 'webhooks.' . str_replace("/", ".", trim($path, "/"));

        return $route_name;
    }

    /**
     * returns webhook request data
     */
    protected function getWebhookData() : Webhook
    {
        return new Webhook(json_decode($this->getRequest()->getContent(), true));
    }

    /**
     * check request data
     */
    protected function isValidWebhookRequest() : bool
    {
        $required = ["event_type", "data", "timestamp", "source"];
        $json = $this->getWebhookData()->getData();
        if (is_array($json) && 
            empty(array_diff($required, array_keys($json)) && 
            empty(array_diff(array_keys($json), $required)))
        ) {
            if (!empty($this->getWebhookEventTypes()) && !in_array($json['event_type'], $this->getWebhookEventTypes())) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * gets Weebook's event_type(s)
     * 
     * @return array
     */
    abstract protected function getWebhookEventTypes(): array;

    /**
     * process Webhook data
     *
     * @param Webhook $webhook
     * @return array
     */
    abstract protected function processWebhook(Webhook $webhook): array;
}
