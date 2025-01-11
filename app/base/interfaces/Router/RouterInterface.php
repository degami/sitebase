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

namespace App\Base\Interfaces\Router;

interface RouterInterface
{
    public const REGEXP_ROUTE_VARIABLE_EXPRESSION = "(:([^{}]*|\{([^{}]*|\{[^{}]*\})*\})*)?";
    public const CLASS_METHOD = 'renderPage';

    /**
     * defines http default verbs
     *
     * @return array
     */
    public function getHttpVerbs(): array;

    /**
     * gets routes
     *
     * @return array
     */
    public function getRoutes(): array;
}