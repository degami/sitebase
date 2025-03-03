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

namespace App\Base\Interfaces\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\Base\Routing\RouteInfo;

interface PageInterface
{
    /**
     * controller action
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     */
    public function process(?RouteInfo $route_info = null, $route_data = []): Response;
}