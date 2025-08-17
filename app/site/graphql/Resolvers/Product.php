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

use App\App;
use App\Base\Interfaces\GraphQl\ResolverInterface;
use App\Base\Models\User;
use App\Base\Models\Website;

class Product implements ResolverInterface
{
    public static function resolve(array $args, mixed $source = null): mixed
    {
        $app = App::getInstance();

        $className = $source['class'] ?? null;
        if (!$className) {
            return null; // or throw an exception if needed
        }

        $productId = $source['id'] ?? null;
        if (!$productId) {
            return null; // or throw an exception if needed
        }

        $product = $app->containerCall([$className, 'load'], [$productId]);

        return $source + $product->getData();
    }
}