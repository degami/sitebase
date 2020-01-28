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
namespace App\Base\Abstracts;

use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Site\Models\RequestLog;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi\Accessories\TagElement;

/**
 * Base for frontend pages
 */
abstract class FrontendPage extends BaseHtmlPage
{
    /**
     * @var string locale
     */
    protected $locale = null;

    /**
     * @var array page regions
     */
    protected $regions = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        if (!$this->getTemplates()->getFolders()->exists('frontend')) {
            $this->getTemplates()->addFolder('frontend', App::getDir(App::TEMPLATES).DS.'frontend');
        }

        foreach ($this->getUtils()->getPageRegions() as $region) {
            $this->regions[$region] = [];
        }

        // 'content' is reserved for Plates
        unset($this->regions['content']);
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getBaseTemplateData()
    {
        $this->getUtils()->getAllPageBlocks($this->getCurrentLocale());

        return parent::getBaseTemplateData() + [
            'locale' => $this->getCurrentLocale(),
            'body_class' => str_replace('.', '-', $this->getRouteName()),
        ];
    }

    /**
     * get page region tags html
     *
     * @param  string $region
     * @return string
     */
    protected function getRegionTags($region)
    {
        if (!isset($this->regions[$region])) {
            return false;
        }

        $output = '';
        foreach ($this->regions[$region] as $key => $tag) {
            $output .= (string) $tag;
        }

        return $output;
    }

    /**
     * adds a tag to page region
     *
     * @param string $region
     * @param TagElement|array $tag
     */
    public function addTag(string $region, $tag)
    {
        if (!isset($this->regions[$region])) {
            return false;
        }

        if (is_array($tag) && isset($tag['tag'])) {
            $tag = new TagElement($tag);
        }

        if ($tag instanceof TagElement) {
            $this->regions[$region][] = $tag;
        }
    }

    /**
     * {@inheritdocs}
     *
     * @return \League\Plates\Template\Template
     */
    protected function prepareTemplate()
    {
        $template = $this->getTemplates()->make('frontend::'.$this->getTemplateName());
        $template->data($this->getTemplateData()+$this->getBaseTemplateData());
        $locale = $template->data()['locale'] ?? $this->getCurrentLocale();

        $template->start('head_scripts');
        echo $this->getAssets()->renderHeadInlineJS();
        $template->stop();

        $template->start('scripts');
        echo $this->getAssets()->renderPageInlineJS();
        $template->stop();

        $template->start('styles');
        echo $this->getAssets()->renderPageInlineCSS();
        $template->stop();

        foreach (array_keys($this->regions) as $region) {
            $template->start($region);
            if ($this->showMenu() && $region == 'menu') {
                echo $this->getHtmlRenderer()->renderSiteMenu($locale);
            }
            echo $this->getRegionTags($region);
            $template->stop();
        }

        return $template;
    }

    /**
     * {@inheritdocs}
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if (!$this->getRouteInfo()->isAdminRoute() && !$this->checkPermission('view_site')) {
            return $this->getUtils()->errorPage(403);
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $return = parent::process($route_info, $route_data);

        try {
            $log = $this->getContainer()->make(RequestLog::class);
            $log->fillWithRequest($this->getRequest(), $this);
            $log->persist();
        } catch (Exception $e) {
            $this->getUtils()->logException($e, "Can't write RequestLog");
            if ($this->getEnv('DEBUG')) {
                return $this->getUtils()->errorException($e);
            }
        }

        return $return;
    }

    /**
     * gets Rewrite object for current page
     *
     * @return Rewrite|null
     */
    public function getRewrite()
    {
        static $rewrite = null;

        if ($rewrite != null) {
            return $rewrite;
        }

        if ($this->getRouteInfo()) {
            if ($this->getRouteInfo()->getRewrite()) {
                // we have rewrite id into RouteInfo
                $rewrite = $this->getDb()->rewrite($this->getRouteInfo()->getRewrite());
            } else {
                // no data into RouteInfo, try by route
                $rewrite = $this->getDb()->rewrite()->where(['route' => $this->getRouteInfo()->getRoute()]);
            }
        }
        return $rewrite;
    }

    /**
     * gets current page's locale
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        if ($this->locale == null) {
            // try by menu
            $rewrite = $this->getRewrite();
            if ($rewrite != null && (($menu_obj = $rewrite->menuList()->fetch()) != null)) {
                $menu_obj = $this->getContainer()->make(\App\Site\Models\Menu::class, ['dbrow' => $menu_obj]);
                $this->locale = $menu_obj->locale;
            } elseif ($rewrite != null) {
                $rewrite = $this->getContainer()->make(\App\Site\Models\Rewrite::class, ['dbrow' => $rewrite]);
                $this->locale = $rewrite->locale;
            }
        }
        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }

    /**
     * get current page translations urls
     *
     * @return array
     */
    public function getTranslations()
    {
        $rewrite = $this->getContainer()->make(\App\Site\Models\Rewrite::class, ['dbrow' => $this->getRewrite()]);
        return array_map(
            function ($el) {
                return $el->url;
            },
            $rewrite->getTranslations()
        );
    }


    /**
     * show menu flag.
     * utility function, subclasses can override this method to disable menu load
     *
     * @return boolean
     */
    public function showMenu()
    {
        return true;
    }

    /**
     * show blocks flag.
     * utility function, subclasses can override this method to disable blocks load
     *
     * @return boolean
     */
    public function showBlocks()
    {
        return true;
    }
}
