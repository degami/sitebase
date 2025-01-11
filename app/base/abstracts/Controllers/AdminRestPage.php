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

namespace App\Base\Abstracts\Controllers;

use App\Base\Routing\RouteInfo;
use App\Base\Traits\AdminTrait;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base for rest endpoints
 */
abstract class AdminRestPage extends BaseRestPage
{
    use AdminTrait;

    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender(): BasePage|Response
    {
        if (!$this->checkCredentials() || !$this->checkPermission(static::getAccessPermission())) {
            throw new PermissionDeniedException();
        }

        return parent::beforeRender();
    }

    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        /** @var JsonResponse $out */
        $out = parent::process($route_info, $route_data);

        if ($this->getEnv('DEBUG')) {
            if ($out instanceof JsonResponse) {
                // add auth info to response
                $data = json_decode($out->getContent(), true);
                $data['auth'] = $this->getTokenData();
                $out->setData($data);
            }
        }

        return $out;
    }


    /**
     * gets access permission name
     *
     * @return string
     */
    abstract public static function getAccessPermission(): string;
}
