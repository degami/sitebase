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
use App\Base\Exceptions\NotFoundException;
use App\Base\Interfaces\GraphQl\ResolverInterface;

class Search implements ResolverInterface
{
    public static function resolve(array $args): mixed
    {
        $input = $args['input'];
        $page = $args['page'] ?? 0;

        $locale = $args['locale'];

        if (!\App\App::getInstance()->getSearch()->isEnabled()) {
            throw new NotFoundException();
        }

        $search_result = static::getSearchResult($input, $locale, $page);
        $docs = $search_result['docs'];
        $total = $search_result['total'];

        return [
            'search_query' => $input,
            'search_result' => array_map(function ($el) {
                return [
                    'frontend_url' => $el['frontend_url'],
                    'title' => $el['title'],
                    'excerpt' => $el['excerpt'],
                ];
            }, $docs),
            'total' => $total,
            'page' => $page,
        ];
    }

    /**
     * gets search results based on query
     *
     * @param string|null $search_query
     * @param string|null $locale
     * @param int $page
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getSearchResult(?string $search_query = null, ?string $locale = null, int $page = 0): array
    {
        $app = App::getInstance();

        if ($search_query == null) {
            return ['total' => 0, 'docs' => []];
        }

        if ($locale == null) {
            $locale = $app->getCurrentLocale();
        }

        return $app->getSearch()
            ->addAndCondition('website_id', $app->getSiteData()->getCurrentWebsiteId())
            ->addAndCondition('locale', $locale)
            ->addOrCondition('content', ':match|'.$search_query)
            ->addOrCondition('title', ':match|'.$search_query)
            //->addOrCondition('date', ':match|'.$search_query) // cannot search on date field as format must be yyyy-MM-dd HH:mm:ss
            ->addSort('created_at', 'desc')
            ->addSort('id', 'asc')
            ->searchData($page);
    }
}