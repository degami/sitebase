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

use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\Controllers\FrontendPageWithObject;
use \App\App;
use \App\Site\Models\Page as PageModel;
use \App\Site\Models\Website;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;

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
     * @param  integer        $id
     * @param  RouteInfo|null $route_info
     * @param  array          $options
     * @return Response
     */
    public function showPage($id, RouteInfo $route_info = null, array $options = [])
    {
        $this->setObject($this->getContainer()->call([PageModel::class, 'load'], ['id' => $id]));
        return $this->renderPage($route_info);
    }

    /**
     * shows homepage
     *
     * @param  RouteInfo|null $route_info
     * @return Response
     */
    public function showFrontPage(RouteInfo $route_info = null)
    {
        $homepage_id = null;
        if ($this->getSiteData()->getHomePageRedirectsToLanguage($this->getSiteData()->getCurrentWebsiteId())) {
            $homepage_id = $this->getSiteData()->getHomePageId(
                $this->getSiteData()->getCurrentWebsiteId(),
                $this->getSiteData()->getBrowserPreferredLanguage()
            );

            if ($homepage_id) {
                $page_model = $this->getContainer()->call([PageModel::class, 'load'], ['id' => $homepage_id]);
                return $this->doRedirect($page_model->getFrontendUrl());
            }
        } else {
            $homepage_id = $this->getSiteData()->getHomePageId($this->getSiteData()->getCurrentWebsiteId(), null);
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
     * @return [type] [description]
     */
    public static function getObjectClass()
    {
        return PageModel::class;
    }
}
