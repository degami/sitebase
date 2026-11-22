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

namespace App\Base\Interfaces\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\Base\Routing\RouteInfo;
use App\Base\Exceptions\PermissionDeniedException;

interface PageInterface
{
    /**
     * controller action
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response;

    /**
     * controller entrypoint
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws PermissionDeniedException
     */
    public function renderPage(?RouteInfo $route_info = null, array $route_data = []): Response;
}