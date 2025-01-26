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

namespace App\Site\GraphQL\Resolvers;

use App\Base\Interfaces\GraphQl\ResolverInterface;

class Greetings implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        return trim(__('Hello').' '.($args['firstName'] ?? '') . ' '.($args['lastName'] ?? ''));
    }
}