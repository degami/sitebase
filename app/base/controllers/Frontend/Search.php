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
use App\Base\Abstracts\Controllers\FrontendPageWithLang;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Tools\Search\Manager as SearchManager;
use App\Base\Tools\Search\AIManager as AISearchManager;

/**
 * Search page
 */
class Search extends FrontendPageWithLang
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Search';

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
        $page = $this->getRequest()->query->get('page') ?? 0;
        $query = $this->getSearchQuery();
        $useAI = $this->getRequest()->query->get('ai') ?? false;

        if ($useAI) {
            $search_result = $this->getAIsearchResult($query, 5);
        } else {
            $search_result = $this->getSearchResult($query, $page);
        }

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
        return $this->getRequest()->query->get('q');
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
     * gets cache key
     */
    public function getCacheKey() : string
    {

        $website_id = $this->getSiteData()->getCurrentWebsiteId();
        $locale = $this->getRewrite()?->getLocale() ?? $this->getSiteData()->getDefaultLocale();
        $page = $this->getRequest()->query->get('page') ?? 0;

        $prefix = 'site.'.$website_id.'.'.$locale.'.';


        $prefix .= trim(str_replace("/", ".", $this->getRouteInfo()->getRouteName()));


        return $this->normalizeCacheKey($prefix . '.q='. $this->getSearchQuery().'.p='.$page);
    }

    protected function getAIsearchResult(?string $search_query = null, int $k = 5, ?string $locale = null, $llmCode = 'googlegemini'): array
    {
        if ($search_query === null) {
            return ['total' => 0, 'docs' => []];
        }

        /** @var AISearchManager $embeddingManager */
        $aiSearchManager = $this->containerMake(AISearchManager::class, [
            'llm' => $this->getAI()->getAIModel($llmCode),
            'model' => match ($llmCode) {
                'googlegemini' => 'text-embedding-004',
                'chatgpt' => 'text-embedding-3-small',
                'claude' => 'claude-2.0-embedding',
                'groq' => 'groq-vector-1',
                'mistral' => 'mistral-embedding-001',
                'perplexity' => 'perplexity-embedding-001',
                default => null,
            }
        ]);
        
        if ($locale === null) {
            $locale = $this->getCurrentLocale();
        }

        $filters = [
            'locale' => $locale,
            'website_id' => $this->getSiteData()->getCurrentWebsiteId()
        ];

        $searchResult = $aiSearchManager->searchNearby($search_query, $k, $filters);

        // Mappiamo i dati per essere compatibili con il template
        return [
            'total' => $searchResult['total'] ?? count($searchResult['docs']),
            'docs' => array_map(function($doc) {

                return $this->getSearch()->getIndexDataForFrontendModel($this->containerCall(
                    [$doc['data']['modelClass'], 'load'],
                    ['id' => $doc['data']['id']]
                ))['_data'];

            }, $searchResult['docs'] ?? [])
        ];
    }    
}
