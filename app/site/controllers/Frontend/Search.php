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

namespace App\Site\Controllers\Frontend;

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPage;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Search page
 */
class Search extends FrontendPage
{
    public const INDEX_NAME = 'sitebase_index';
    public const RESULTS_PER_PAGE = 10;

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getEnv('ELASTICSEARCH'));
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

        $total = $search_result['hits']['total']['value'] ?? 0;
        $hits = $search_result['hits']['hits'] ?? [];

        return [
            'search_query' => $this->getSearchQuery(),
            'search_result' => array_map(function ($el) {
                return $el['_source'];
            }, $hits),
            'total' => $total,
            'page' => $page,
            'paginator' => $this->getHtmlRenderer()->renderPaginator($page, $total, $this, self::RESULTS_PER_PAGE),
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
    protected function getSearchResult($search_query = null, $page = 0): array
    {
        if ($search_query == null) {
            return [];
        }

        $client = $this->getElasticsearch();

        $params = [
            'index' => self::INDEX_NAME,
            'body' => [
                'from' => $page * self::RESULTS_PER_PAGE,
                'size' => self::RESULTS_PER_PAGE,
                'query' => [
                    "bool" => [
                        'minimum_should_match' => 1,
                        "should" => [
                            ['match' => ['content' => $search_query]],
                            ['match' => ['title' => $search_query]],
                            ['match' => ['date' => $search_query]],
                        ],
                        "filter" => [
                            ["term" => ["website_id" => $this->getCurrentWebsiteId()]],
                            ["term" => ["locale" => $this->getCurrentLocale()]],
                        ],
                    ],
                ],
            ]
        ];

        return $client->search($params);
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
}
