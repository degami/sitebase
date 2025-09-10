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

namespace App\Site\Controllers\Frontend\Cms;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\FrontendPageWithLang;
use App\Site\Models\News;
use App\Base\Routing\RouteInfo;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * News List Page
 */
class NewsList extends FrontendPageWithLang
{
    /**
     * @var RouteInfo|null route info object
     */
    protected ?RouteInfo $route_info = null;

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup(): string
    {
        return '';
    }

    /**
     * return route path
     *
     * @return array
     */
    public static function getRoutePath(): array
    {
        return ['frontend.cms.newslist' => 'news', 'frontend.cms.newslist.withlang' => '/{lang:[a-z]{2}}/news'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'news_list';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getTemplateData(): array
    {
        /** @var \App\Base\Abstracts\Models\BaseCollection $collection */
        $collection = $this->containerCall([News::class, 'getCollection']);
        $collection->addCondition(['locale' => $this->getCurrentLocale()])->addOrder(['date' => 'DESC']);
        $data = $this->containerCall([$collection, 'paginate'] /*, ['page_size' => 10]*/);
        return $this->template_data += [
            'page_title' => $this->getUtils()->translate('News', locale: $this->getCurrentLocale()),
            'news' => $data['items'],
            'total' => $data['total'],
            'current_page' => $data['page'],
            'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this, $data['page_size']),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentLocale(): string
    {
        if (!$this->locale) {
            $this->locale = parent::getCurrentLocale();
            if ($this->locale == null) {
                $this->locale = 'en';
            }
        }
        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }
}
