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

use Degami\Basics\Exceptions\BasicException;
use Exception;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\Site\Models\News;
use App\Base\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Exceptions\NotFoundException;
use Throwable;

/**
 * News Detail Page
 */
class NewsDetail extends FrontendPageWithObject
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'news/{id:\d+}';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['GET'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'news_detail';
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws Exception
     * @throws NotFoundException
     * @throws Throwable
     */
    public function process(?RouteInfo $route_info = null, $route_data = []): Response
    {
        if (!($this->getObject() instanceof News && $this->getObject()->isLoaded())) {
            throw new NotFoundException();
        }

        return parent::process($route_info);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     */
    public function getBaseTemplateData(): array
    {
        $out = parent::getBaseTemplateData();
        $out ['body_class'] = str_replace('.', '-', $this->getRouteName()) . ' news-' . $this->getObject()->id;
        return $out;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return News::class;
    }
}
