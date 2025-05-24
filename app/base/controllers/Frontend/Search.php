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

namespace App\Base\Controllers\Frontend;

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPage;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Tools\Search\Manager as SearchManager;

/**
 * Search page
 */
class Search extends FrontendPage
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getSearch()->isEnabled());
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getRoutePath(): array
    {
        return ['frontend.search' => 'search', 'frontend.search.withlang' => '{lang:[a-z]{2}}/search'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getRouteGroup(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'search';
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeRender() : BasePage|Response
    {
        if ($this->getRouteInfo()->getVar('lang') != null) {
            $this->locale = $this->getRouteInfo()->getVar('lang');
        }
        try {
            return parent::beforeRender();
        } catch (PermissionDeniedException | BasicException $e) {
            return $this->containerCall([$this->getUtils(), 'exceptionPage'], ['exception' => $e, 'route_info' => $this->getAppRouteInfo()]);
        }
    }

    /**
     * {@inheritdoc }
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getTemplateData(): array
    {
        $page = $this->getRequest()->get('page') ?? 0;
        $search_result = $this->getSearchResult($this->getSearchQuery(), $page);

        return [
            'search_query' => $this->getSearchQuery(),
            'search_result' => $search_result['docs'],
            'total' => $search_result['total'],
            'page' => $page,
            'paginator' => $this->getHtmlRenderer()->renderPaginator($page, $search_result['total'], $this, SearchManager::RESULTS_PER_PAGE),
        ];
    }

    /**
     * gets searched string
     *
     * @return mixed
     */
    protected function getSearchQuery(): mixed
    {
        return $this->getRequest()->get('q');
    }

    /**
     * gets search results based on query
     *
     * @param string|null $search_query
     * @param int $page
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getSearchResult(?string $search_query = null, int $page = 0): array
    {
        if ($search_query == null) {
            return ['total' => 0, 'docs' => []];
        }

        return $this->getSearch()
            ->addAndCondition('website_id', $this->getSiteData()->getCurrentWebsiteId())
            ->addAndCondition('locale', $this->getCurrentLocale())
            ->addOrCondition('content', ':match|'.$search_query)
            ->addOrCondition('title', ':match|'.$search_query)
            //->addOrCondition('date', ':match|'.$search_query) // cannot search on date field as format must be yyyy-MM-dd HH:mm:ss
            ->addSort('created_at', 'desc')
            ->addSort('id', 'asc')
            ->searchData($page);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('Search', locale: $this->getCurrentLocale());
    }

    /**
     * gets cache key
     */
    public function getCacheKey() : string
    {

        $website_id = $this->getSiteData()->getCurrentWebsiteId();
        $locale = $this->getRewrite()?->getLocale() ?? $this->getSiteData()->getDefaultLocale();
        $page = $this->getRequest()->get('page') ?? 0;

        $prefix = 'site.'.$website_id.'.'.$locale.'.';


        $prefix .= trim(str_replace("/", ".", $this->getRouteInfo()->getRouteName()));


        return $this->normalizeCacheKey($prefix . '.q='. $this->getSearchQuery().'.p='.$page);
    }
}
