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

use App\Base\Traits\AdminTrait;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base JSON page on admin
 */
abstract class AdminJsonPage extends BaseJsonPage
{
    use AdminTrait;

    /**
     * {@inheritdoc}
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

    /**
     * gets url by route_name and params
     *
     * @param string $route_name
     * @param array $route_params
     * @return string
     * @throws BasicException
     */
    public function getUrl(string $route_name, array $route_params = []): string
    {
        return $this->getAdminRouter()->getUrl($route_name, $route_params);
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract public static function getAccessPermission(): string;
}
