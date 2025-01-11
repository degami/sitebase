<?php

namespace App\Site\GraphQL\Resolvers;

use App\App;
use App\Base\Interfaces\GraphQl\ResolverInterface;

class Translations implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        $translationsPath = App::getDir(App::TRANSLATIONS);
        $translationsArr = include($translationsPath.DS.'en.php');
        $keys = array_keys($translationsArr);

        return array_map(fn ($key) => ['key' => $key, 'value' => __($key)], $keys);
    }
}