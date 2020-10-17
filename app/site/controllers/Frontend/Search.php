<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Controllers\Frontend;


use App\Base\Abstracts\Controllers\FrontendPage;

/**
 * Search page
 */
class Search extends FrontendPage
{
    const INDEX_NAME = 'sitebase_index';
    const RESULTS_PER_PAGE = 10;

    /**
     * {@inheritdoc }
     *
     * @return array
     */
    public static function getRoutePath()
    {
        return ['frontend.search' => 'search', 'frontend.search.withlang' => '{lang:[a-z]{2}}/search'];
    }

    /**
     * {@inheritdoc }
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return '';
    }

    /**
     * {@inheritdoc }
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'search';
    }

    /**
     * {@inheritdoc }
     */
    protected function beforeRender()
    {
        if ($this->getRouteInfo()->getVar('lang') != null) {
            $this->locale = $this->getRouteInfo()->getVar('lang');
        }
        return parent::beforeRender();
    }

    /**
     * {@inheritdoc }
     */
    protected function getTemplateData()
    {
        $page = $this->getRequest()->get('page') ?? 0;
        $search_result = $this->getSearchResult($this->getSearchQuery(), $page);

        $total = $search_result['hits']['total']['value'];
        $hits = $search_result['hits']['hits'];

        return [
            'search_query' => $this->getSearchQuery(),
            'search_result' => array_map(function($el){
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
     * @return mixed|null
     */
    protected function getSearchQuery()
    {
        return $this->getRequest()->get('q');
    }

    /**
     * gets search results based on query
     *
     * @param string|null $search_query
     * @param int $page
     * @return array
     */
    protected function getSearchResult($search_query = null, $page = 0)
    {
        $client = $this->getElasticsearch();

        $params = [
            'index' => self::INDEX_NAME,
            'body'  => [
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

        $result = $client->search($params);

        return $result;
    }
}