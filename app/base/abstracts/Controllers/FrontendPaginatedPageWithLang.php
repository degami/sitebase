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

namespace App\Base\Abstracts\Controllers;

use App\App;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base for frontend listing pages with language in route path
 */
abstract class FrontendPaginatedPageWithLang extends FrontendPageWithLang
{

    /**
     * gets cache key - as this is a paginated page, we should take in place also the offset page parameter
     */
    public function getCacheKey() : string
    {

        /** @var Request $request */
        $request = App::getInstance()->getEnvironment()->getRequest();

        $page = $request?->query?->get('page') ?? 0;

        if ($this->getRewriteObject() == null) {
            return $this->normalizeCacheKey(
                'site.' . $this->getSiteData()->getCurrentWebsiteId() . 
                '.' . trim($this->getRouteInfo()->getRoute(), '.') . 
                '.p'.$page
            );            
        }

        return $this->normalizeCacheKey(
            'site.'.$this->getRewriteObject()?->getWebsiteId().
            '.' . $this->getRewriteObject()?->getLocale() . 
            '.' . trim($this->getRouteInfo()->getRouteName() ?? "", '.') . 
            '.p'.$page
        );
    }
}
