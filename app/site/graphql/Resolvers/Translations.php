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

class Translations implements ResolverInterface
{
    public static function resolve(array $args, mixed $source = null): mixed
    {
        $translationsPath = App::getDir(App::TRANSLATIONS);
        $translationsArr = include($translationsPath.DS.'en.php');
        $keys = array_keys($translationsArr);

        return array_map(fn ($key) => ['key' => $key, 'value' => __($key)], $keys);
    }
}