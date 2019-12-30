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
use \App\Base\Abstracts\FrontendPageWithObject;
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
     * @return string
     */
    public static function getRoutePath()
    {
        return 'page/{id:\d+}';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        if ($this->getObject() instanceof PageModel && $this->getObject()->isLoaded()) {
            if (!empty($this->getObject()->getTemplateName())) {
                return $this->getObject()->getTemplateName();
            }
            $homepage_id = $this->getSiteData()->getHomePageId($this->getSiteData()->getCurrentWebsite(), $this->getObject()->getLocale());
            if ($this->getObject()->getId() == $homepage_id) {
                return 'homepage';
            }
        }

        if (is_array($this->route_info->getHandler()) && $this->route_info->getHandler()[1] == 'showFrontPage') {
            return 'homepage';
        }

        return 'page';
    }

    /**
     * {@inheritdocs}
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $this->route_info = $route_info;
        
        if (isset($route_data['id'])) {
            $this->setObject($this->getContainer()->call([PageModel::class, 'load'], ['id' => $route_data['id']]));
        }

        if (!($this->getObject() instanceof PageModel && $this->getObject()->isLoaded())) {
            return $this->getUtils()->errorPage(404);
        }

        return parent::process($route_info);
    }

    /**
     * shows a page
     * @param  integer         $id
     * @param  RouteInfo|null $route_info
     * @param  array          $options
     * @return Response
     */
    public function showPage($id, RouteInfo $route_info = null, array $options = [])
    {
        $this->setObject($this->getContainer()->call([PageModel::class, 'load'], ['id' => $id]));
        return $this->process($route_info);
    }

    /**
     * shows homepage
     * @param  RouteInfo|null $route_info
     * @return Response
     */
    public function showFrontPage(RouteInfo $route_info = null)
    {
        $homepage_id = null;
        if ($this->getSiteData()->getHomePageRedirectsToLanguage($this->getSiteData()->getCurrentWebsite())) {
            $homepage_id = $this->getSiteData()->getHomePageId(
                $this->getSiteData()->getCurrentWebsite(),
                $this->getSiteData()->getBrowserPreferredLanguage()
            );

            if ($homepage_id) {
                $page_model = $this->getContainer()->call([PageModel::class, 'load'], ['id' => $homepage_id]);
                return $this->doRedirect($page_model->getFrontendUrl());
            }
        } else {
            $homepage_id = $this->getSiteData()->getHomePageId($this->getSiteData()->getCurrentWebsite(), null);
        }

        if ($homepage_id) {
            return $this->showPage($homepage_id, $route_info);
        }

        // if page was not found, try to redirect to language
        return $this->doRedirect("/".$this->getSiteData()->getBrowserPreferredLanguage());
    }

    /**
     * gets page model id
     * @return integer
     */
    public function getPageId()
    {
        return $this->getObject()->getId();
    }

    /**
     * {@inheritdocs}
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
     * @return [type] [description]
     */
    public static function getObjectClass()
    {
        return PageModel::class;
    }
}
