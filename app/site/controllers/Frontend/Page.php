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

use App\Base\Abstracts\Controllers\BaseHtmlPage;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \App\Base\Abstracts\Controllers\FrontendPageWithObject;
use \App\Site\Models\Page as PageModel;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;
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
    public static function getRoutePath()
    {
        return 'page/{id:\d+}';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['GET'];
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function getTemplateName()
    {
        if ($this->isHomePage()) {
            return 'homepage';
        }

        if ($this->getObject() && $this->getObject()->getTemplateName()) {
            return $this->getObject()->getTemplateName();
        }

        return 'page';
    }

    /**
     * shows a page
     *
     * @param $id
     * @param RouteInfo|null $route_info
     * @return BaseHtmlPage|BasePage|mixed|Response
     * @throws BasicException
     * @throws PermissionDeniedException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function showPage($id, RouteInfo $route_info = null)
    {
        if ($this->getEnv('DEBUG')) {
            $debugbar = $this->getDebugbar();
            $debugbar['time']->startMeasure('showpage');
        }
        $this->setObject($this->getContainer()->call([PageModel::class, 'load'], ['id' => $id]));
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
    public function showFrontPage(RouteInfo $route_info = null)
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
                /** @var \App\Site\Models\Page $page_model */
                $page_model = $this->getContainer()->call([PageModel::class, 'load'], ['id' => $homepage_id]);
                return $this->doRedirect($page_model->getFrontendUrl());
            }
        } else {
            $homepage_id = $this->getSiteData()->getHomePageId($website_id, $route_vars['lang'] ?? null);
        }

        if (!$homepage_id) {
            $homepage_id = $this->getSiteData()->getHomePageId($website_id, null);
            if ($homepage_id) {
                /** @var \App\Site\Models\Page $page_model */
                $page_model = $this->getContainer()->call([PageModel::class, 'load'], ['id' => $homepage_id]);
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
        return $this->doRedirect("/".$browser_locale);
    }

    /**
     * gets page model id
     *
     * @return integer
     */
    public function getPageId()
    {
        return $this->getObject()->getId();
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     */
    protected function getBaseTemplateData()
    {
        $out = parent::getBaseTemplateData();
        $out ['body_class'] = str_replace('.', '-', $this->getRouteName()).' page-'. $this->getObject()->id;
        return $out;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public static function getObjectClass()
    {
        return PageModel::class;
    }
}
