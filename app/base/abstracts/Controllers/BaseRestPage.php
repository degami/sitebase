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
use \App\Site\Models\RequestLog;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \Exception;

/**
 * Base for rest endopoints
 */
abstract class BaseRestPage extends BasePage
{
    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, Request $request = null)
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
            return $this->getUtils()->errorPage(403);
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
     * @return \App\Base\Abstracts\Models\BaseModel
     */
    protected function loadObject($id)
    {
        if (!is_subclass_of($this->getObjectClass(), \App\Base\Abstracts\Models\BaseModel::class)) {
            return null;
        }

        return $this->getContainer()->call([$this->getObjectClass(), 'load'], [ 'id' => $id]);
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
        if (!empty($data = json_decode($this->getRequest()->getContent(), true))) {
            if (isset($data['id'])) {
                unset($data['id']);
            }
        }

        try {
            $log = $this->getContainer()->make(RequestLog::class);
            $log->fillWithRequest($this->getRequest(), $this);
            $log->persist();
        } catch (Exception $e) {
            $this->getUtils()->logException($e, "Can't write RequestLog");
            if ($this->getEnv('DEBUG')) {
                return $this->getUtils()->errorException($e);
            }
        }

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
                break;
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
                break;
            case 'DELETE':
                // Delete
                $old_data = $object->getData();
                unset($old_data['id']);
                $object->delete();

                return $this
                    ->getResponse()
                    ->prepare($this->getRequest())
                    ->setData($old_data);
                break;
        }

        return $return;
    }

    /**
     * gets object class name for method
     *
     * @return string
     */
    abstract public static function getObjectClass();
}
