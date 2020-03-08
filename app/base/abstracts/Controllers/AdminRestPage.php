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
use \App\Base\Traits\AdminTrait;
use \Exception;

/**
 * Base for rest endopoints
 */
abstract class AdminRestPage extends BaseRestPage
{

    use AdminTrait;


    /**
     * before render hook
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            return $this->getUtils()->errorPage(403);
        }

        return parent::beforeRender();
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract protected function getAccessPermission();
}
