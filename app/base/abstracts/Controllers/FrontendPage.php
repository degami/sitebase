<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Abstracts\Controllers;

use App\Base\Traits\FrontendPageTrait;
use App\Site\Models\Menu;
use App\Site\Models\Rewrite;
use App\Site\Models\Website;
use DebugBar\DebugBarException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use League\Plates\Template\Template;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\App;
use App\Site\Models\RequestLog;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Html\TagElement;
use App\Base\Exceptions\PermissionDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Base for frontend pages
 */
abstract class FrontendPage extends BaseHtmlPage
{
    use FrontendPageTrait;

    /**
     * @var Rewrite|null rewrite
     */
    protected ?Rewrite $rewrite = null;

    /**
     * @var array page regions
     */
    protected array $regions = [];

    /**
     * {@inheritdoc}
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);

        $this->getTemplates()->setDirectory(App::getDir(App::TEMPLATES) . DS . 'frontend');

        if (!$this->getTemplates()->getFolders()->exists('frontend')) {
            $this->getTemplates()->addFolder('frontend', App::getDir(App::TEMPLATES) . DS . 'frontend');
        }

        if (!$this->getTemplates()->getFolders()->exists('theme') && $this->getSiteData()->getThemeName() != null) {
            $this->getTemplates()->addFolder('theme', App::getDir(App::TEMPLATES) . DS . 'frontend' . DS . $this->getSiteData()->getThemeName(), true);
            $themeFile = $this->getTemplates()->getFolders()->get('theme')->getPath() . DS . 'theme.php';
            if (file_exists($themeFile)) {
                @include_once($themeFile);
            }
        }

        foreach ($this->getSiteData()->getPageRegions() as $region) {
            $this->regions[$region] = [];
        }

        // 'content' is reserved for Plates
        unset($this->regions['content']);
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
        $this->getSiteData()->getAllPageBlocks($this->getCurrentLocale());

        return parent::getBaseTemplateData() + [
                'locale' => $this->getCurrentLocale(),
                'body_class' => str_replace('.', '-', $this->getRouteName()),
            ];
    }

    /**
     * get page region tags html
     *
     * @param string $region
     * @return bool|string
     */
    protected function getRegionTags(string $region): bool|string
    {
        if (!isset($this->regions[$region])) {
            return false;
        }

        $output = '';
        foreach ($this->regions[$region] as $key => $tag) {
            $output .= (string)$tag;
        }

        return $output;
    }

    /**
     * adds a tag to page region
     *
     * @param string $region
     * @param TagElement|array $tag
     *
     * @return self|bool
     */
    public function addTag(string $region, TagElement|array $tag): bool|self
    {
        if (!isset($this->regions[$region])) {
            return false;
        }

        if (is_array($tag) && isset($tag['tag'])) {
            $tag = $this->containerMake(TagElement::class, ['options' => $tag]);
        }

        if ($tag instanceof TagElement) {
            $this->regions[$region][] = $tag;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return Template
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function prepareTemplate(): Template
    {
        $templateName = $this->alterTemplateName($this->getTemplateName());

        if ($this->getTemplates()->getFolders()->exists('theme')) {
            $template = $this->getTemplates()->make('theme::' . $templateName);
        } else {
            // fallback to "frontend"
            $template = $this->getTemplates()->make('frontend::' . $templateName);
        }

        $template->data($this->getTemplateData() + $this->getBaseTemplateData());
        $locale = $template->data()['locale'] ?? $this->getCurrentLocale();

        $template->start('head_styles');
        echo $this->getAssets()->renderHeadCSS();
        $template->stop();

        $template->start('head_scripts');
        echo $this->getAssets()->renderHeadJsScripts();
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
     * {@inheritdoc}
     *
     * @return Response|self
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        if (!$this->getRouteInfo()->isAdminRoute() && !$this->checkPermission('view_site')) {
            throw new PermissionDeniedException();
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return BasePage|Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PermissionDeniedException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     * @throws DebugBarException
     */
    public function renderPage(RouteInfo $route_info = null, $route_data = []) : BasePage|Response
    {
        $return = parent::renderPage($route_info, $route_data);

        if ($this->getSiteData()->getConfigValue('app/frontend/log_requests') == true) {
            try {
                $log = $this->containerMake(RequestLog::class);
                $log->fillWithRequest($this->getRequest(), $this);
                $log->setResponseCode($return instanceof Response ? $return->getStatusCode() : 200);
                $log->persist();
            } catch (Exception $e) {
                $this->getUtils()->logException($e, "Can't write RequestLog", $this->getRequest());
                if ($this->getEnv('DEBUG')) {
                    return $this->getUtils()->exceptionPage($e, $this->getRequest(), $this->getRouteInfo());
                }
            }
        }

        return $return;
    }

    /**
     * gets Rewrite object for current page
     *
     * @param bool $reset
     * @return Rewrite|null
     */
    public function getRewrite($reset = false): ?Rewrite
    {
        if ($this->rewrite != null && !$reset) {
            return $this->rewrite;
        }

        if ($this->getRouteInfo()) {
            try {
                if ($this->getRouteInfo()->getRewrite()) {
                    // we have rewrite id into RouteInfo
                    $this->rewrite = $this->containerCall([Rewrite::class, 'load'], ['id' => $this->getRouteInfo()->getRewrite()]);
                } else {
                    // no data into RouteInfo, try by route
                    $this->rewrite = $this->containerCall([Rewrite::class, 'loadBy'], ['field' => 'route', 'value' => $this->getRouteInfo()->getRoute()]);
                }
            } catch (Exception $e) {
            }
        }
        return $this->rewrite;
    }

    /**
     * gets current page's locale
     *
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentLocale(): ?string
    {
        if ($this->locale == null) {
            // try by menu
            $rewrite = $this->getRewrite();
            if (($menu_obj = $rewrite?->menuList()->fetch()) != null) {
                /** @var Menu $menu_obj */
                $menu_obj = $this->containerMake(Menu::class, ['db_row' => $menu_obj]);
                $this->locale = $menu_obj->getLocale();
            } elseif ($rewrite != null) {
                $this->locale = $rewrite->getLocale();
            }

            if ($this->locale == null && $this->getRouteData('locale') != null) {
                $this->locale = $this->getRouteData('locale');
            }

            if ($this->locale == null && $this->getCurrentUser()) {
                $this->locale = $this->getCurrentUser()->getLocale();
            }

            if ($this->locale == null) {
                $this->locale = $this->getSiteData()->getDefaultLocale();
            }
        }
        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }

    /**
     * gets current website id
     *
     * @return int|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentWebsiteId(): int|null
    {
        return $this->getSiteData()->getCurrentWebsiteId();
    }

    /**
     * gets current website model
     *
     * @return Website|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentWebsite(): ?Website
    {
        return $this->getSiteData()->getCurrentWebsite();
    }

    /**
     * get current page translations urls
     *
     * @return array
     * @throws BasicException
     */
    public function getTranslations(): array
    {
        return array_map(
            function ($el) {
                return $el->url;
            },
            $this->getRewrite()?->getTranslations() ?? []
        );
    }


    /**
     * show menu flag.
     * utility function, subclasses can override this method to disable menu load
     *
     * @return bool
     */
    public function showMenu(): bool
    {
        return true;
    }

    /**
     * show blocks flag.
     * utility function, subclasses can override this method to disable blocks load
     *
     * @return bool
     */
    public function showBlocks(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function canBeFPC(): bool
    {
        return true;
    }    
}
