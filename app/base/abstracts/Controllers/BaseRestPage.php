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

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Tools\Utils\Globals;
use Degami\Basics\Exceptions\BasicException;
use \Psr\Container\ContainerInterface;
use \App\Site\Routing\RouteInfo;
use \App\Site\Models\RequestLog;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \Exception;
use \App\Base\Exceptions\PermissionDeniedException;
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
     * @param Request|null $request
     * @throws BasicException
     */
    public function __construct(ContainerInterface $container, Request $request)
    {
        parent::__construct($container, $request);
        $this->response = $this->getContainer()->get(JsonResponse::class);
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT'];
    }

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     */
    protected function beforeRender()
    {
        $return = parent::beforeRender();
        if ($return instanceof Response) {
            return $return;
        }

        if ($this->getRequest()->headers->get('Content-Type') != 'application/json' &&
            $this->getRequest()->getContentType() != 'json'
        ) {
            throw new PermissionDeniedException();
        }

        return $this;
    }

    /**
     * get Request HTTP verb
     *
     * @return string
     */
    protected function getVerb()
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * loads object by id
     *
     * @param  integer $id
     * @return BaseModel
     */
    protected function loadObject($id)
    {
        if (!is_subclass_of($this->getObjectClass(), BaseModel::class)) {
            return null;
        }

        return $this->getContainer()->call([$this->getObjectClass(), 'load'], [ 'id' => $id]);
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
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        if (!empty($data = json_decode($this->getRequest()->getContent(), true))) {
            if (isset($data['id'])) {
                unset($data['id']);
            }
        }

        try {
            $log = $this->getContainer()->make(RequestLog::class);
            $log->fillWithRequest($this->getRequest(), $this);
            $log->setResponseCode(200);
            $log->persist();
        } catch (Exception $e) {
            $this->getUtils()->logException($e, "Can't write RequestLog", $this->getRequest());
            if ($this->getEnv('DEBUG')) {
                return $this->getUtils()->exceptionPage($e);
            }
        }

        /** @var BaseModel $object */
        $object = $this->getContainer()->call([$this->getObjectClass(), 'new']);
        if (in_array($this->getVerb(), ['GET', 'PUT', 'DELETE'])) {
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
                        }, $this->getContainer()->call([$this->getObjectClass(), 'all'])));
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

        return $this->getContainer()->call([$this->getUtils(), 'errorPage'], ['error_code' => 500]);
    }

    /**
     * gets object class name for method
     *
     * @return string
     */
    abstract public static function getObjectClass();
}
