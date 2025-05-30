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

use App\Base\Exceptions\PermissionDeniedException;
use DebugBar\DebugBarException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\Site\Models\Page as PageModel;
use App\Base\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * A Site Page
 */
class Page extends FrontendPageWithObject
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'page/{id:\d+}';
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
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getTemplateName(): string
    {
        if ($this->isHomePage()) {
            return 'homepage';
        }

        return 'page';
    }

    /**
     * shows a page
     *
     * @param $id
     * @param RouteInfo|null $route_info
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PermissionDeniedException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     * @throws DebugBarException
     */
    public function showPage(int $id, ?RouteInfo $route_info = null): mixed
    {
        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->startMeasure('showpage');
        }
        $this->setObject($this->containerCall([PageModel::class, 'load'], ['id' => $id]));
        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->stopMeasure('showpage');
        }
        return $this->renderPage($route_info);
    }

    /**
     * shows homepage
     *
     * @param RouteInfo|null $route_info
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException|Throwable
     */
    public function showFrontPage(?RouteInfo $route_info = null): Response
    {
        $route_vars = $route_info->getVars();

        $website_id = $this->getCurrentWebsiteId();
        $browser_locale = $this->getSiteData()->getBrowserPreferredLanguage();

        $homepage_id = null;
        if (isset($route_vars['lang']) || $this->getSiteData()->getHomePageRedirectsToLanguage($website_id)) {
            $homepage_id = $this->getSiteData()->getHomePageId(
                $website_id,
                $route_vars['lang'] ?? $browser_locale
            );

            if ($homepage_id) {
                /** @var PageModel $page_model */
                if (isset($route_vars['lang'])) {
                    return $this->showPage($homepage_id, $route_info);
                } else {
                    $page_model = $this->containerCall([PageModel::class, 'load'], ['id' => $homepage_id]);
                    return $this->doRedirect("/".$page_model->getLocale()."/");    
                }
            }
        } else {
            $homepage_id = $this->getSiteData()->getHomePageId($website_id, $route_vars['lang'] ?? null);
        }

        if (!$homepage_id) {
            $homepage_id = $this->getSiteData()->getHomePageId($website_id, null);
            if ($homepage_id) {
                /** @var PageModel $page_model */
                $page_model = $this->containerCall([PageModel::class, 'load'], ['id' => $homepage_id]);
                $translations = $page_model->getTranslations();
                if (isset($translations[$route_vars['lang'] ?? $browser_locale])) {
                    return $this->doRedirect($translations[$route_vars['lang'] ?? $browser_locale]);
                }
            }
        }

        if ($homepage_id) {
            return $this->showPage($homepage_id, $route_info);
        }

        // if page was not found, try to redirect to language
        return $this->doRedirect("/" . $browser_locale);
    }

    /**
     * gets page model id
     *
     * @return int
     */
    public function getPageId(): int
    {
        return $this->getObject()->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getBaseTemplateData(): array
    {
        $out = parent::getBaseTemplateData();
        $out ['body_class'] = str_replace('.', '-', $this->getRouteName()) . ' page-' . $this->getObject()->id;
        return $out;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return PageModel::class;
    }
}
