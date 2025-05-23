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

namespace App\Base\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Models\Menu;
use App\Base\Traits\AdminTrait;
use App\Base\Models\Rewrite;
use Degami\Basics\Html\TagElement;
use App\Base\Routing\RouteInfo;
use App\App;

/**
 * Breadcrumbs Block
 */
class BreadCrumbs extends BaseCodeBlock
{
    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderHTML(?BasePage $current_page = null, array $data = []): string
    {
        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        if (empty($config)) {
            $config = [
                'add-current' => false,
            ];
        }

        $locale = $current_page?->getCurrentLocale();
        $website_id = $this->getSiteData()->getCurrentWebsiteId();
        $route_info = $current_page?->getRouteInfo();

        // $current_page_handler = $route_info->getHandler();
        if ($current_page?->getRouteGroup() == AdminPage::getRouteGroup() || $route_info?->isAdminRoute()) {
            return '';
        }

        if ($route_info->getRewrite() == null) {
            $rewriteId = Rewrite::getCollection()->where(['route' => $route_info->getRoute(), 'locale' => $locale])?->getFirst()?->getId();
            if (is_int($rewriteId)) {
                $route_info->setRewrite($rewriteId);
            }
        }

        $menu_item = Menu::getCollection()->where(['rewrite_id' => $route_info?->getRewrite()])->getFirst();
        $home_url = $this->getWebRouter()->getUrl('frontend.root');

        /** @var TagElement $breadcrumbs_links */
        $breadcrumbs_links = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'ol',
            'attributes' => [
                'class' => 'breadcrumb',
            ],
        ]]);

        $homepageid = $this->getSiteData()->getHomePageId($website_id, $locale);

        if (!$current_page?->isHomePage()) {
            $li = $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'li',
                    'attributes' => ['class' => 'breadcrumb-item'],
                ]]
            );

            $atag = $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'a',
                    'attributes' => [
                        'class' => 'breadcrumb-link',
                        'href' => $home_url,
                        'title' => $this->getUtils()->translate('Home', locale: $locale),
                    ],
                    'text' => $this->getUtils()->translate('Home', locale: $locale),
                ]]
            );

            $li->addChild($atag);
            $breadcrumbs_links->addChild($li);
        }

        if ($menu_item instanceof Menu) {
            $breadcrumbs = explode('/', $menu_item->getBreadcrumb());
            if (in_array($menu_item->getId(), $breadcrumbs)) {
                // remove it as it will be added (if needed)
                if (($key = array_search($menu_item->getId(), $breadcrumbs)) !== false) {
                    unset($breadcrumbs[$key]);
                }
            }

            $atags = array_map(
                function ($id) use ($homepageid, $locale) {
                    $menuItem = $this->containerCall([Menu::class, 'load'], ['id' => $id]);

                    if ($menuItem->getRewriteId()) {
                        /**
                         * @var Rewrite $rewrite
                         */
                        $rewrite = $this->containerCall([Rewrite::class, 'load'], ['id' => $menuItem->getRewriteId()]);
                        if ($rewrite->getRoute() == '/page/' . $homepageid) {
                            $menuItem->setTitle($this->getUtils()->translate('Home', locale: $locale));
                        }
                    }

                    $leaf = [
                        'title' => $menuItem->getTitle(),
                        'href' => $menuItem->getLinkUrl(),
                        'target' => $menuItem->getTarget(),
                    ];
                    return $this->renderLink($leaf);
                },
                array_filter($breadcrumbs)
            );
            foreach ($atags as $atag) {
                $li = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'li',
                        'attributes' => ['class' => 'breadcrumb-item'],
                    ]]
                );

                $li->addChild($atag);
                $breadcrumbs_links->addChild($li);
            }
        } else if ($route_info?->getRewrite()) {
            $rewrite = $this->containerCall([Rewrite::class, 'load'], ['id' => $route_info->getRewrite()]);
            $parentIds = explode("/", $rewrite?->getParentIds());
            array_pop($parentIds);

            $atags = array_map(
                function ($id) use ($homepageid, $locale) {
                    /**
                     * @var Rewrite $rewrite
                     */
                    $rewrite = $this->containerCall([Rewrite::class, 'load'], ['id' => $id]);

                    /**
                     * @var MenuItem $menuItem
                     */
                    $menuItem = Menu::getCollection()->where(['rewrite_id' => $id])->getFirst();

                    if ($menuItem instanceof Menu) {
                        $title = $menuItem->getTitle();
                    } else {
                        /** 
                         * @var FrontendPage $currentPage 
                         */
                        $currentPage = static::getControllerByRewrite($rewrite, App::getInstance());
                        $title = $currentPage->getRouteName();
                    }

                    if ($rewrite->getRoute() == '/page/' . $homepageid) {
                        $title = $this->getUtils()->translate('Home', locale: $locale);
                    }

                    $leaf = [
                        'title' => $title,
                        'href' => $rewrite->getUrl(),
                    ];
                    return $this->renderLink($leaf);
                },
                array_filter($parentIds)
            );
            foreach ($atags as $atag) {
                $li = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'li',
                        'attributes' => ['class' => 'breadcrumb-item'],
                    ]]
                );

                $li->addChild($atag);
                $breadcrumbs_links->addChild($li);
            }
        }

        if (
            ($config['add-current'] == true) &&
            ($current_page instanceof FrontendPage)
        ) {
            $li = $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'li',
                    'attributes' => ['class' => 'breadcrumb-item'],
                ]]
            );

            $atag = null;
            if ($current_page->isHomePage()) {
                $atag = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'a',
                        'attributes' => [
                            'class' => 'breadcrumb-link',
                            'href' => $home_url,
                            'title' => $this->getUtils()->translate('Home', locale: $locale),
                        ],
                        'text' => $this->getUtils()->translate('Home', locale: $locale),
                    ]]
                );    
            } else if ($current_page instanceof FrontendPageWithObject && $current_page->getRewrite()) {
                $atag = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'a',
                        'attributes' => [
                            'class' => 'breadcrumb-link',
                            'href' => $current_page->getRewrite()->getUrl(),
                            'title' => $current_page->getObjectTitle(),
                        ],
                        'text' => $current_page->getObjectTitle(),
                    ]]
                );
            } else if ($menu_item instanceof Menu) {
                $atag = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'a',
                        'attributes' => [
                            'class' => 'breadcrumb-link',
                            'href' => $menu_item->getLinkUrl(),
                            'title' => $menu_item->getTitle(),
                        ],
                        'text' => $menu_item->getTitle(),
                    ]]
                );
            } else {
                $atag = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'a',
                        'attributes' => [
                            'class' => 'breadcrumb-link',
                            'href' => $current_page->getControllerUrl(),
                            'title' => $current_page->getRouteName(),
                        ],
                        'text' => $current_page->getRouteName(),
                    ]]
                );
            }

            $li->addChild($atag);

            $breadcrumbs_links->addChild($li);
        }

        if (count($breadcrumbs_links->getChildren()) == 0) {
            return '';
        }

        /** @var TagElement $breadcrumbs_container */
        $breadcrumbs_container = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'nav',
            'attributes' => [
                'aria-label' => 'breadcrumb',
            ],
        ]]);

        $breadcrumbs_container->addChild($breadcrumbs_links);
        return $breadcrumbs_container;
    }

    /**
     * internally renders menu link
     *
     * @param array $leaf
     * @param string $link_class
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function renderLink(array $leaf, $link_class = 'breadcrumb-link'): string
    {
        $link_options = [
            'tag' => 'a',
            'attributes' => [
                'class' => $link_class,
                'href' => $leaf['href'],
                'title' => $leaf['title'],
            ],
            'text' => $leaf['title'],
        ];

        if ($leaf['target'] ?? null) {
            $link_options['attributes']['target'] = $leaf['target'];
        }

        return $this->containerMake(TagElement::class, ['options' => $link_options]);
    }


    /**
     * additional configuration fieldset
     *
     * @param FAPI\Form $form
     * @param $form_state
     * @param $default_values
     * @return array
     * @throws FAPI\Exceptions\FormException
     */
    public function additionalConfigFieldset(FAPI\Form $form, &$form_state, $default_values): array
    {
        $config_fields = [];

        $config_fields[] = $form->getFieldObj('add-current', [
            'type' => 'switchbox',
            'title' => 'Add current page to breadcrumb',
            'default_value' => boolval($default_values['add-current'] ?? '') ? 1 : 0,
            'yes_value' => 1,
            'yes_label' => 'Yes',
            'no_value' => 0,
            'no_label' => 'No',
            'field_class' => 'switchbox',
        ]);

        return $config_fields;
    }

    /**
     * gets route group
     *
     * @return string|null
     */
    public static function getRouteGroup(): ?string
    {
        return (trim(getenv('ADMINPAGES_GROUP')) != null) ? '/' . getenv('ADMINPAGES_GROUP') : null;
    }


    protected static function getControllerByRewrite(Rewrite $rewrite, App $app)
    {
        /** @var RouteInfo $routeInfo */
        $routeInfo = $rewrite->getRouteInfo();
        return static::getControllerByRouteInfo($routeInfo, $app);
    }

    protected static function getControllerByRouteInfo(RouteInfo $routeInfo, App $app)
    {
        $handler = $routeInfo->getHandler();

        $handlerType = reset($handler); $handlerMethod = end($handler);
        $currentPage = $app->containerMake($handlerType);

        $vars = $routeInfo->getVars();

        // inject container into vars
        //$vars['container'] = $this->getContainer();

        // inject request object into vars
        //$vars['request'] = $this->getRequest();

        // inject routeInfo
        $vars['route_info'] = $routeInfo;

        // add route collected data
        $vars['route_data'] = $routeInfo->getVars();
        $vars['route_data']['_noLog'] = true;

        $currentPage->setRouteInfo($routeInfo);

        if ($currentPage instanceof FrontendPageWithObject) {
            $app->containerCall([$currentPage, $handlerMethod], $vars);
        }

        return $currentPage;
    }
}
