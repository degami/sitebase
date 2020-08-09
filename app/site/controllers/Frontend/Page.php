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
     * {@inheritdocs}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     */
    protected function beforeRender()
    {
        $route_data = $this->getRouteData();

        if (isset($route_data['id'])) {
            $this->setObject($this->getContainer()->call([PageModel::class, 'load'], ['id' => $route_data['id']]));
        }

        return parent::beforeRender();
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
        $this->setObject($this->getContainer()->call([PageModel::class, 'load'], ['id' => $id]));
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

        $homepage_id = null;
        if (isset($route_vars['lang']) || $this->getSiteData()->getHomePageRedirectsToLanguage($this->getSiteData()->getCurrentWebsiteId())) {
            $homepage_id = $this->getSiteData()->getHomePageId(
                $this->getSiteData()->getCurrentWebsiteId(),
                $route_vars['lang'] ?? $this->getSiteData()->getBrowserPreferredLanguage()
            );

            if ($homepage_id) {
                $page_model = $this->getContainer()->call([PageModel::class, 'load'], ['id' => $homepage_id]);
                return $this->doRedirect($page_model->getFrontendUrl());
            }
        } else {
            $homepage_id = $this->getSiteData()->getHomePageId($this->getSiteData()->getCurrentWebsiteId(), $route_vars['lang'] ?? null);
        }

        if ($homepage_id) {
            return $this->showPage($homepage_id, $route_info);
        }

        // if page was not found, try to redirect to language
        return $this->doRedirect("/".$this->getSiteData()->getBrowserPreferredLanguage());
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
