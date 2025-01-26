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
use App\Site\Models\Website;

class Configuration implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        $app = App::getInstance();

        $websites = Website::getCollection()->getItems();

        $configPaths = [
            'app/frontend/homepage',
            'app/frontend/homepage_redirects_to_language',
            'app/frontend/langs',
            'app/frontend/main_menu',
            'app/frontend/assets_domain',
            'app/frontend/menu_with_logo',
        ];

        $out = [];

        foreach ($websites as $website) {
            $website_id = $website->getId();

            foreach([null] + $app->getSiteData()->getSiteLocales($website_id) as $locale) {
                $configs = [];
                foreach ($configPaths as $path) {
                    $value = $app->getSiteData()->getConfigValue($path, $website_id, $locale);
                    if ($value == null && $path == 'app/frontend/homepage') {
                        $configs[] = ['path' => $path, 'value' => $app->getSiteData()->getConfigValue($path, $website_id, 'en')];
                    } else {
                        $configs[] = ['path' => $path, 'value' => $value];
                    }
                }

                
                $configs[] = ['path' => 'app/mapbox/api_key', 'value' => $app->getEnv('MAPBOX_API_KEY')];
                $configs[] = ['path' => 'app/googlemaps/api_key', 'value' => $app->getEnv('GOOGLE_API_KEY')];

                $out[] = [
                    'website' => $website,
                    'locale' => $locale,
                    'configs' => $configs,
                ];
            }
        }

        return $out;
    }
}