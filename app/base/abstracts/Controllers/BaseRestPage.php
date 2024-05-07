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

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Tools\Utils\Globals;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use App\Site\Routing\RouteInfo;
use App\Site\Models\RequestLog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use App\Base\Exceptions\PermissionDeniedException;
use Throwable;

/**
 * Base for rest endpoints
 */
abstract class BaseRestPage extends BasePage
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
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
        $this->response = $this->getContainer()->get(JsonResponse::class);
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT'];
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        $return = parent::beforeRender();
        if ($return instanceof Response) {
            return $return;
        }

        if ($this->getRequest()->headers->get('Content-Type') != 'application/json' && $this->getRequest()->getContentType() != 'json') {
            throw new PermissionDeniedException();
        }

        return $this;
    }

    /**
     * get Request HTTP verb
     *
     * @return string
     */
    protected function getVerb(): string
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * loads object by id
     *
     * @param int $id
     * @return BaseModel|null
     */
    protected function loadObject($id): ?BaseModel
    {
        if (!is_subclass_of($this->getObjectClass(), BaseModel::class)) {
            return null;
        }

        return $this->containerCall([$this->getObjectClass(), 'load'], ['id' => $id]);
    }

    /**
     * {@inheritdocs}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws Throwable
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        if (!empty($data = json_decode($this->getRequest()->getContent(), true))) {
            if (isset($data['id'])) {
                unset($data['id']);
            }
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

        /** @var BaseModel $object */
        $object = $this->containerCall([$this->getObjectClass(), 'new']);
        if (in_array($this->getVerb(), ['GET', 'PUT', 'DELETE']) && isset($route_data['id'])) {
            $object = $this->loadObject($route_data['id']);
        }

        switch ($this->getVerb()) {
            case 'POST':
            case 'PUT':
                // Create
                // Update

                $object->setData($data);
                $object->save();
                return $this
                    ->getResponse()
                    ->prepare($this->getRequest())
                    ->setData($object->getData());
            case 'GET':
                // Read

                if ($object->id == null) {
                    return $this
                        ->getResponse()
                        ->prepare($this->getRequest())
                        ->setData(array_map(function ($object) {
                            return $object->getData();
                        }, $this->containerCall([$this->getObjectClass(), 'getCollection'])->getItems()));
                }

                return $this
                    ->getResponse()
                    ->prepare($this->getRequest())
                    ->setData($object->getData());
            case 'DELETE':
                // Delete
                $old_data = $object->getData();
                unset($old_data['id']);
                $object->delete();

                return $this
                    ->getResponse()
                    ->prepare($this->getRequest())
                    ->setData($old_data);
        }

        return $this->containerCall([$this->getUtils(), 'errorPage'], ['error_code' => 500, 'route_info' => $route_info]);
    }

    /**
     * gets object class name for method
     *
     * @return string
     */
    abstract public static function getObjectClass(): string;
}
