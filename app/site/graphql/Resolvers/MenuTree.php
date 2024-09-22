<?php

namespace App\Site\GraphQL\Resolvers;

use App\App;
use App\Base\GraphQl\ResolverInterface;

class MenuTree implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        $app = App::getInstance();

        $locale = $args['locale'];
        if ($locale == null) {
            $locale = $app->getSiteData()->getDefaultLocale();
        }

        $website_id = $args['website_id'];
        $menu_name = $args['menu_name'];

        $tree = $app->getSiteData()->getSiteMenu($menu_name, $website_id, $locale, null, false);
        return $tree;
    }
}