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

namespace App\Base\Tools\Utils;

use App\Base\Models\AdminActionLog;
use App\Base\Models\CronLog;
use App\Base\Models\MailLog;
use App\Base\Models\Menu;
use App\Base\Models\QueueMessage;
use App\Base\Models\ApplicationLog;
use App\Base\Models\RequestLog;
use Degami\Basics\Exceptions\BasicException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Models\Rewrite;
use App\Base\Routing\RouteInfo;
use App\App;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Controllers\Admin\Login;
use App\Base\Models\Block;
use App\Base\Models\OrderShipment;
use App\Base\Models\ProgressManagerProcess;
use App\Base\Models\Website;
use App\Base\Tools\DataCollector\BlocksDataCollector;
use Degami\Basics\Html\TagElement;
use Degami\Basics\Html\TagList;
use chillerlan\QRCode\QRCode;
use Exception;

/**
 * Html Parts Renderer Helper Class
 */
class HtmlPartsRenderer extends ContainerAwareObject
{
    /**
     * returns flash message html
     *
     * @param BasePage $controller
     * @return TagList
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderFlashMessages(BasePage $controller): TagList
    {
        $cookie_flash_messages = (array)$controller->getFlashMessages();
        $direct_flash_messages = (array)$controller->getFlashMessages(true);
        $flash_messages = array_merge_recursive($cookie_flash_messages, $direct_flash_messages);

        $controller->dropFlashMessages();

        $messages_container = $this->containerMake(TagList::class);

        foreach ($flash_messages as $type => $messages) {
            $messages_list = $this->containerMake(TagList::class);

            foreach ($messages as $message) {
                $messages_list->addChild(
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'div',
                            'text' => $message,
                        ]]
                    )
                );
            }

            $messages_container->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'div',
                        'attributes' => ['class' => "alert alert-" . $type],
                        'text' => (string)$messages_list,
                    ]]
                )
            );
        }

        return $messages_container;
    }

    /**
     * internally renders menu link
     *
     * @param array $leaf
     * @param string $link_class
     * @return TagElement
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function renderMenuLink(array $leaf, string $link_class = 'nav-link'): TagElement
    {
        $link_options = [
            'tag' => 'a',
            'attributes' => [
                'class' => $link_class,
                'href' => $leaf['href'],
            ],
            'text' => $leaf['title'],
        ];
        if ($leaf['target']) {
            $link_options['attributes']['target'] = $leaf['target'];
        }
        if (!empty($leaf['children'])) {
            $link_options['id'] = 'navbarDropdown-' . $leaf['id'];
            $link_options['attributes']['role'] = 'button';
            $link_options['attributes']['data-toggle'] = 'dropdown';
            $link_options['attributes']['aria-haspopup'] = 'true';
            $link_options['attributes']['aria-expanded'] = 'false';
        }

        return $this->containerMake(TagElement::class, ['options' => $link_options]);
    }

    /**
     * internally renders site menu
     *
     * @param array $menu_tree
     * @param array|null $parent
     * @return TagElement
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function internalRenderSiteMenu(array $menu_tree, ?array $parent = null): TagElement
    {
        $tag_options = [
            'tag' => ($parent == null) ? 'ul' : 'div',
            'attributes' => [
                'class' => ($parent == null) ? 'navbar-nav mr-auto' : 'dropdown-menu',
            ],
        ];

        if ($parent != null) {
            $tag_options['attributes']['aria-labelledby'] = 'navbarDropdown-' . $parent['id'];
        }

        $out = $this->containerMake(TagElement::class, ['options' => $tag_options]);

        if ($parent && $parent['href'] != '#') {
            $out->addChild($this->renderMenuLink($parent));
        }

        foreach ($menu_tree as $leaf) {
            $leaf_container = ($parent == null) ?
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'li',
                        'attributes' => ['class' => 'nav-item'],
                    ]]
                ) :
                $this->containerMake(TagList::class);

            if (isset($leaf['children']) && !empty($leaf['children'])) {
                $leaf_container->addChild($this->renderMenuLink($leaf, 'nav-link dropdown-toggle'));
                $parent_item = $leaf;
                unset($parent_item['children']);
                $leaf_container->addChild($this->internalRenderSiteMenu($leaf['children'], $parent_item));
            } else {
                $leaf_container->addChild($this->renderMenuLink($leaf));
            }

            $out->addChild($leaf_container);
        }

        return $out;
    }

    /**
     * render site menu
     *
     * @param string $locale
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function renderSiteMenu(string $locale): ?string
    {
        $website_id = $this->getSiteData()->getCurrentWebsiteId();

        if (empty($locale)) {
            return null;
        }

        $menu_name = $this->getSiteData()->getMainMenuName($website_id, $locale);
        if (empty($menu_name)) {
            return null;
        }

        $cache_key = strtolower('site.' . $website_id . '.menu.html.' . $locale);
        if ($this->getCache()->has($cache_key)) {
            return $this->getCache()->get($cache_key);
        }

        // preload menu items
        /** @var \App\Base\Abstracts\Models\BaseCollection $collection */
        $collection = $this->containerCall([Menu::class, 'getCollection']);
        $collection->addCondition(['menu_name' => $menu_name, 'website_id' => $website_id]);
        $menuitems = $collection->getItems();

        usort($menuitems, function ($a, $b) {
            if ($a->position == $b->position) {
                return 0;
            }
            return ($a->position < $b->position) ? -1 : 1;
        });

        $menu = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'nav',
                'attributes' => ['class' => 'navbar navbar-expand-lg navbar-light'],
            ]]
        );

        if ($this->getSiteData()->getShowLogoOnMenu($website_id)) {
            $menu->addChild($this->renderSiteLogo());
        }

        // add mobile toggle button
        $button = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'button',
                'type' => 'button',
                'attributes' => [
                    'class' => 'navbar-toggler',
                    'data-toggle' => 'collapse',
                    'data-target' => '#navbarSupportedContent',
                    'aria-controls' => 'navbarSupportedContent',
                    'aria-expanded' => 'false',
                    'aria-label' => 'Toggle navigation'
                ],
            ]]
        );
        $button->addChild(
            $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'span',
                    'attributes' => [
                        'class' => 'navbar-toggler-icon',
                    ],
                ]]
            )
        );
        $menu->addChild($button);

        // add menu content
        $menu_content = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'attributes' => [
                    'class' => 'collapse navbar-collapse',
                ],
                'id' => 'navbarSupportedContent',
            ]]
        );

        $menu_content->addChild($this->internalRenderSiteMenu($this->getSiteData()->buildSiteMenu($menuitems)));
        $menu->addChild($menu_content);

        // store into cache
        $this->getCache()->set($cache_key, (string)$menu);
        return (string)$menu;
    }

    public function renderSiteLogo(?Website $website = null) : TagElement
    {
        // add logo
        $logo = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'img',
                'attributes' => [
                    'class' => '',
                    'title' => $this->getEnvironment()->getVariable('APPNAME'),
                    'src' => $this->getAssets()->assetUrl('/sitebase_logo.png', $website?->getId()),
                ],
            ]]
        );

        $atag = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'navbar-brand',
                    'href' => $this->getWebRouter()->getUrl('frontend.root'),
                    'title' => $this->getEnvironment()->getVariable('APPNAME'),
                ],
            ]]
        );

        $atag->addChild($logo);
        return $atag;
    }

    /**
     * render region blocks
     *
     * @param string $region
     * @param null $locale
     * @param BasePage|null $current_page
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function renderBlocks(string $region, ?string $locale = null, ?BasePage $current_page = null): ?string
    {
        static $pageBlocks = null;
        static $current_rewrite = null;

        $website_id = $this->getSiteData()->getCurrentWebsiteId();

        /** @var DebugBar $debugbar */
        $debugbar = App::getInstance()->getDebugbar();

        $route_info = null;
        $cache_key = strtolower('site.' . $website_id  . '.' . $locale . '.blocks.' . $region);
        if ($current_page) {
            if (method_exists($current_page, 'showBlocks') && $current_page->showBlocks() === false) {
                return '';
            }

            $route_info = $current_page->getRouteInfo();
            if ($route_info instanceof RouteInfo) {
                //$cache_key = strtolower('site.' . $website_id . '.' . trim(str_replace("/", ".", $route_info->getRoute()), '.') . '.blocks.' . $region . '.html.' . $locale);
                $cache_key = $current_page->getCacheKey() . '.blocks.' . $region;
                if ($route_info->isAdminRoute()) {
                    $cache_key = null;
                }
            }
        }

        if (!empty($cache_key) && $this->getCache()->has($cache_key)) {

            if ($debugbar->hasCollector(BlocksDataCollector::NAME)) {
                /** @var BlocksDataCollector $dataCollector */
                $dataCollector = $debugbar->getCollector(BlocksDataCollector::NAME);
                $dataCollector->addElements($region, "In Cache", null, 0);
            }

            return $this->getCache()->get($cache_key);
        }

        if ($current_rewrite == null && ($route_info instanceof RouteInfo) && is_numeric($route_info->getRewrite())) {
            $current_rewrite = $this->containerCall([Rewrite::class, 'load'], ['id' => $route_info->getRewrite()]);
        }

        if (is_null($pageBlocks)) {
            $pageBlocks = $this->getSiteData()->getAllPageBlocks($locale);
        }

        $out = "";
        if (isset($pageBlocks[$region])) {
            foreach ($pageBlocks[$region] as $block) {
                /** @var Block $block */
                if ((!$block->isCodeBlock() && $locale != $block->locale) || (!is_null($current_rewrite) && !$block->checkValidRewrite($current_rewrite))) {
                    continue;
                }

                $measure_key = 'render block: ' . $block->getId();

                if (App::getInstance()->getEnvironment()->canDebug()) {
                    $debugbar['time']->startMeasure($measure_key);
                }

                $before = microtime(true);
                if (is_callable([$block->getRealInstance(), 'isCachable']) && !$block->getRealInstance()->isCachable()) {
                    $out .= $this->renderUncachableBlockTag($block, $current_page, $locale);
                } else {
                    $out .= $block->render($current_page);
                }
                $after = microtime(true);

                if (App::getInstance()->getEnvironment()->canDebug()) {
                    $debugbar['time']->stopMeasure($measure_key);

                    if ($debugbar->hasCollector(BlocksDataCollector::NAME)) {
                        /** @var BlocksDataCollector $dataCollector */
                        $dataCollector = $debugbar->getCollector(BlocksDataCollector::NAME);
                        $dataCollector->addElements($region, get_class($block), ['id' => $block->getId()], ($after - $before));
                    }
                }
            }
        }
        if (!empty($cache_key)) {
            $this->getCache()->set($cache_key, $out);
        }
        return $out;
    }

    public function renderUncachableBlockTag(Block $block, ?BasePage $current_page, ?string $locale = null) : string
    {
        if (!$current_page) {
            return "";
        }
        $out = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'div',
            'attributes' => [
                'class' => 'uncachable-block',
                'data-uncachable' => json_encode([
                    'block_id' => $block->getId(),
                    'url' => $current_page->getControllerUrl(), 
                    'route' => $current_page->getRouteInfo()->getRouteName(),
                    'rewrite' => $current_page->getRouteInfo()->getRewrite(),
                    'route_vars' => $current_page->getRouteInfo()->getVars(),
                    'locale' => $locale ?? $this->getApp()->getCurrentLocale(),
                ]),
            ],
        ]]);
        return (string) $out;
    }

    /**
     * gets paginator li html tag
     *
     * @param string $li_class
     * @param string|null $href
     * @param string $text
     * @return TagElement
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function getPaginatorLi(string $li_class, ?string $href, string $text): TagElement
    {
        $li_options = [
            'tag' => 'li',
            'attributes' => ['class' => $li_class],
        ];
        if (empty($href)) {
            $li_options['text'] = $text;
        }
        $li = $this->containerMake(TagElement::class, ['options' => $li_options]);

        if (!empty($href)) {
            $li->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'a',
                        'attributes' => [
                            'class' => 'page-link',
                            'href' => $href,
                        ],
                        'text' => $text,
                    ]]
                )
            );
        }

        return $li;
    }

    /**
     * renders paginator
     *
     * @param int $current_page
     * @param int $total
     * @param BasePage $controller
     * @param int $page_size
     * @param int $visible_links
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderPaginator(int $current_page, int $total, BasePage $controller, int $page_size = BaseCollection::ITEMS_PER_PAGE, int $visible_links = 2): string
    {
        $total_pages = ceil($total / $page_size) - 1;
        if ($total_pages < 1) {
            return '';
        }

        $current_base = $controller->getControllerUrl();
        $query_params = $controller->getRequest()->query->all();
        unset($query_params['page']);

        $out = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'nav',
                'attributes' => ['class' => 'd-flex justify-content-end', 'aria-label' => 'Paginator'],
            ]]
        );

        $ul = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'ul',
                'attributes' => ['class' => 'pagination'],
            ]]
        );

        $out->addChild($ul);

        // add "first" link
        $ul->addChild(
            $this->getPaginatorLi(
                'page-item' . (($current_page == 0) ? ' disabled' : ''),
                $current_base . '?' . http_build_query($query_params + ['page' => 0]),
                $this->getUtils()->translate('First', locale: $controller->getCurrentLocale())
            )
        );

        if ($current_page > 0) {
            // add "previous" link
            $ul->addChild(
                $this->getPaginatorLi(
                    'page-item',
                    $current_base . '?' . http_build_query($query_params + ['page' => ($current_page - 1)]),
                    $this->containerMake(
                        TagList::class
                    )->addChild(
                        $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'span',
                                'attributes' => [
                                    'class' => '',
                                    'aria-hidden' => "true",
                                ],
                                'text' => '&laquo;',
                            ]]
                        )
                    )->addChild(
                        $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'span',
                                'attributes' => [
                                    'class' => 'sr-only',
                                ],
                                'text' => $this->getUtils()->translate('Previous', locale: $controller->getCurrentLocale()),
                            ]]
                        )
                    )
                )
            );
        }

        if ((max(0, $current_page - $visible_links)) > 0) {
            $ul->addChild(
                $this->getPaginatorLi(
                    'page-item disabled',
                    null,
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'span',
                            'attributes' => [
                                'class' => 'page-link',
                            ],
                            'text' => '...',
                        ]]
                    )
                )
            );
        }

        for ($i = max(0, $current_page - $visible_links); $i <= min($current_page + $visible_links, $total_pages); $i++) {
            $ul->addChild(
                $this->getPaginatorLi(
                    'page-item' . (($current_page == $i) ? ' active' : ''),
                    $current_base . '?' . http_build_query($query_params + ['page' => $i]),
                    ($i + 1)
                )
            );
        }

        if ((min($current_page + $visible_links, $total_pages)) < $total_pages) {
            $ul->addChild(
                $this->getPaginatorLi(
                    'page-item disabled',
                    null,
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'span',
                            'attributes' => [
                                'class' => 'page-link',
                            ],
                            'text' => '...',
                        ]]
                    )
                )
            );
        }

        if ($current_page < $total_pages) {
            // add "next" link
            $ul->addChild(
                $this->getPaginatorLi(
                    'page-item',
                    $current_base . '?' . http_build_query($query_params + ['page' => ($current_page + 1)]),
                    $this->containerMake(
                        TagList::class
                    )->addChild(
                        $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'span',
                                'attributes' => [
                                    'class' => '',
                                    'aria-hidden' => "true",
                                ],
                                'text' => '&raquo;',
                            ]]
                        )
                    )->addChild(
                        $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'span',
                                'attributes' => [
                                    'class' => 'sr-only',
                                ],
                                'text' => $this->getUtils()->translate('Next', locale: $controller->getCurrentLocale()),
                            ]]
                        )
                    )
                )
            );
        }

        // add "last" link
        $ul->addChild(
            $this->getPaginatorLi(
                'page-item' . (($current_page == $total_pages) ? ' disabled' : ''),
                $current_base . '?' . http_build_query($query_params + ['page' => $total_pages]),
                $this->getUtils()->translate('Last', locale: $controller->getCurrentLocale())
            )
        );

        return (string)$out;
    }

    /**
     * renders admin table
     *
     * @param array $elements
     * @param null $header
     * @param BasePage|null $current_page
     * @return string
     * @throws BasicException
     * @throws OutOfRangeException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderAdminTable(array $elements, ?array $header = null, ?BasePage $current_page = null, string $table_id = 'listing-table'): string
    {
        $selectCheckboxes = false;
        foreach ($elements as $elem) {
            if (isset($elem['_admin_table_item_pk'])) {
                $selectCheckboxes = true;
                break;
            }
        }


        $table = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'table',
                'width' => '100%',
                'id' => $table_id,
                'cellspacing' => '0',
                'cellpadding' => '0',
                'border' => '0',
                'attributes' => ['class' => "table table-striped"],
            ]]
        );

        $thead = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
            ]]
        );
        $tbody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );
        $tfoot = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tfoot',
            ]]
        );

        $table->addChild($thead);
        $table->addChild($tbody);
        $table->addChild($tfoot);

        if (empty($header)) {
            $header = [];
            foreach ($elements as $key => $elem) {
                $header = array_unique(array_merge($header, array_filter(array_keys($elem), fn ($el) => !str_starts_with($el, '_'))));
            }
            $header = array_flip($header);
        }

        $add_searchrow = false;
        if (count($elements) > 0 && $current_page instanceof BasePage) {
            $search_row = $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'tr',
                ]]
            );

            if ($selectCheckboxes == true) {
                $search_row->addChild(
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'text' => '&nbsp;',
                            'scope' => 'col',
                            'attributes' => ['class' => 'nowrap'],
                        ]]
                    )
                );
            }


            //$style="max-width:100%;font-size: 9px;line-height: 11px;min-width: 100%;padding: 3px 1px;margin: 0;border: 1px solid #555;border-radius: 2px;";
            foreach ($header as $k => $v) {
                $className = strtolower($k);
                if (is_array($v) && isset($v['search']) && boolval($v['search']) == true) {
                    $searchqueryparam = (is_array($current_page->getRequest()->query->all('search')) && isset($current_page->getRequest()->query->all('search')[$v['search']])) ? $current_page->getRequest()->query->all('search')[$v['search']] : '';

                    // locale is a well known special case - but can be overridden
                    if ($v['search'] == 'locale' && ($v['input_type'] ?? 'select') == 'select') {
                        $select_options = ['' => '-- '.$this->getUtils()->translate('All', locale: $current_page->getCurrentLocale()).' --'] + $this->getUtils()->getSiteLanguagesSelectOptions();
                        $select_options = array_map(function ($val, $key) use ($searchqueryparam) {
                            $selected = ($key == $searchqueryparam) ? ' selected="selected"': '';
                            return '<option value="' . $key . '"'.$selected.'>' . $val . '</option>';
                        }, $select_options, array_keys($select_options));
    

                        $td = $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'td',
                                'attributes' => ['class' => 'small '. $className, 'style' => 'width: 100px;'],
                                'text' => '<select name="search[' . $v['search'] . ']" style="width: 100px;">' . implode("", $select_options) . '</select>',
                            ]]
                        );    
                    } else {
                        $td = $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'td',
                                'attributes' => ['class' => 'small '. $className],
                                'text' => '<input class="form-control" name="search[' . $v['search'] . ']" value="' . $searchqueryparam . '"/>',
                            ]]
                        );
                    }
                    $add_searchrow = true;
                } else if (is_array($v) && isset($v['foreign']) && boolval($v['foreign']) == true) {
                    $foreignqueryparam = (is_array($current_page->getRequest()->query->all('foreign')) && isset($current_page->getRequest()->query->all('foreign')[$v['foreign']])) ? $current_page->getRequest()->query->all('foreign')[$v['foreign']] : '';

                    $dbtable = $this->getSchema()->getTable($v['table']);
                    $select_options = ['' => '-- '.$this->getUtils()->translate('All', locale: $current_page->getCurrentLocale()).' --'];
                    foreach ($dbtable->getForeignKeys() as $fkobj) {
                        if (in_array($v['foreign'], $fkobj->getColumns())) {
                            $foreign_key = $fkobj->getTargetColumns()[0];
                            $stmt = $this->getDb()->table(
                                $fkobj->getTargetTable()
                            );

                            foreach ($stmt as $row) {
                                $select_options[$row->{$foreign_key}] = $row->{$v['view']};
                            }
                        }
                    }

                    $select_options = array_map(function ($val, $key) use ($foreignqueryparam) {
                        $selected = ($key == $foreignqueryparam) ? ' selected="selected"': '';
                        return '<option value="' . $key . '"'.$selected.'>' . $val . '</option>';
                    }, $select_options, array_keys($select_options));

                    $td = $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'attributes' => ['class' => 'small'],
                            'text' => '<select name="foreign[' . $v['foreign'] . ']">' . implode("", $select_options) . '</select>',
                        ]]
                    );

                    $add_searchrow = true;
                } else {
                    $td = $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'attributes' => ['class' => 'small'],
                            'text' => '&nbsp;',
                        ]]
                    );
                }

                $search_row->addChild($td);
            }
            if ($add_searchrow) {
                $tbody->addChild($search_row);
            }
        }

        // tbody

        if (count($elements)) {
            $rownum = 0;
            foreach ($elements as $key => $elem) {
                // ensure all header cols are in row cols
                $elem += array_combine(array_keys($header), array_fill(0, count($header), ''));

                $trAttributes = ['class' => $rownum++ % 2 == 0 ? 'odd' : 'even'];
                if (isset($elem['_admin_table_item_pk'])) {
                    $trAttributes['data-_item_pk'] = $elem['_admin_table_item_pk'];
                    $trAttributes['class'] .= ' selectable';
                }
                $dblClicElem = current(array_filter(array_keys((array) ($elem['actions'] ?? [])), fn($el) => str_ends_with($el, '.dblclick')));
                if ($dblClicElem || isset($elem['actions'][AdminManageModelsPage::EDIT_BTN])) {
                    $linkHtml = $elem['actions'][$dblClicElem ? $dblClicElem : AdminManageModelsPage::EDIT_BTN];
                    if (preg_match('/<a .*?href=["\'](.*?)["\'].*?>/', $linkHtml, $matches)) {
                        $trAttributes['data-dblclick'] = $matches[1];
                    }
                }

                $row = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'tr',
                        'attributes' =>  $trAttributes,
                    ]]
                );

                if ($selectCheckboxes == true) {

                    $checkboxText = '&nbsp;';
                    if (isset($elem['_admin_table_item_pk']) && (
                        isset($elem['actions'][AdminManageModelsPage::EDIT_BTN]) ||
                        isset($elem['actions'][AdminManageModelsPage::DELETE_BTN])                        
                    )) {
                        $checkboxText = '<label class="checkbox"><input class="table-row-selector" type="checkbox" /><span class="checkbox__icon"></span></label>';
                    }

                    $row->addChild(
                        $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'td',
                                'text' => $checkboxText,
                                'scope' => 'col',
                                'attributes' => ['class' => 'nowrap'],
                            ]]
                        )
                    );
                }

                foreach ($elem as $tk => $td) {
                    if ($tk == 'actions' || $tk == '_admin_table_item_pk') {
                        continue;
                    }
                    $row->addChild(
                        ($td instanceof TagElement && $td->getTag() == 'td') ? $td :
                            $this->containerMake(
                                TagElement::class,
                                ['options' => [
                                    'tag' => 'td',
                                    'text' => (string)$td,
                                    'attributes' => ['class' => in_array(strtolower($tk), ['website', 'locale']) ? 'nowrap' : 'text-break'],
                                ]]
                            )
                    );
                }

                if (array_key_exists('actions', $header)) {
                    $row->addChild(
                        $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'td',
                                'text' => is_array($elem['actions']) ? implode(" ", $elem['actions']) : $elem['actions'] ?? '',
                                'attributes' => ['class' => 'text-right nowrap'],
                            ]]
                        )
                    );
                }

                $tbody->addChild($row);
            }
        } else {
            $text = 'No elements found!';
            if (($current_page instanceof BasePage)) {
                $text = $this->getUtils()->translate($text, locale: $current_page?->getCurrentLocale());
            }

            $row = $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'tr',
                    'attributes' => ['class' => 'odd'],
                ]]
            );

            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'td',
                        'text' => $text,
                        'attributes' => ['class' => 'text-center nowrap', 'colspan' => count($header)],
                    ]]
                )
            );

            $tbody->addChild($row);
        }

        // thead

        $row = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
                'attributes' => ['class' => "thead-dark"],
            ]]
        );

        if ($selectCheckboxes == true) {            
            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'th',
                        'text' => '<label class="checkbox"><input type="checkbox" id="listing-table-toggle-all" /><span class="checkbox__icon"></span></label>',
                        'scope' => 'col',
                        'attributes' => ['class' => 'nowrap'],
                    ]]
                )
            );
        }

        foreach ($header as $th => $column) {
            $className = strtolower($th);
            $th = $this->getUtils()->translate($th, locale: $current_page?->getCurrentLocale());
            if ($current_page instanceof BasePage) {
                $request_params = $current_page->getRequest()->query->all();

                if (!empty($column)) {
                    $orderby = null;
                    if (is_array($column)) {
                        if (isset($column['order'])) {
                            $orderby = $column['order'];
                        }
                    } else {
                        $orderby = $column;
                    }
                    if (!empty($orderby)) {
                        $val = 'DESC';
                        if (isset($request_params['order'][$orderby])) {
                            $val = ($request_params['order'][$orderby] == 'ASC') ? 'DESC' : 'ASC';
                        }
                        $request_params['order'][$orderby] = $val;
                        $th = '<a class="ordering" href="' . ($current_page->getControllerUrl() . '?' . http_build_query($request_params)) . '">' . $th . $this->getIcon($val == 'DESC' ? 'arrow-down' : 'arrow-up') . '</a>';
                    }
                }
            }

            if ($th == 'actions') {
                $th = '';
            }
            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'th',
                        'text' => $th,
                        'scope' => 'col',
                        'attributes' => ['class' => 'nowrap '.$className],
                    ]]
                )
            );
        }
        $thead->addChild($row);

        if (($current_page instanceof BasePage)) {
            $request_params = $current_page->getRequest()->query->all();
            if (isset($request_params['order']) || isset($request_params['search'])) {
                $request_params_nosearch = $request_params;
                unset($request_params_nosearch['search']);
                $add_query_parameters = http_build_query($request_params_nosearch);
                if (strlen($add_query_parameters)) {
                    $add_query_parameters = '?' . $add_query_parameters;
                }
                $current_page->addActionLink('reset-btn', 'reset-btn', $this->getUtils()->translate('Reset', locale: $current_page->getCurrentLocale()), $current_page->getControllerUrl() . $add_query_parameters, 'btn btn-sm btn-outline-warning');
            }
            if ($add_searchrow) {
                $query_params = '';
                if (!empty($request_params)) {
                    $query_params = (array)$request_params;
                    unset($query_params['search']);
                    $query_params = http_build_query($query_params);
                }
                $current_page->addActionLink('search-btn', 'search-btn', $this->getIcon('zoom-in') . $this->getUtils()->translate('Search', locale: $current_page->getCurrentLocale()), $current_page->getControllerUrl() . (!empty($query_params) ? '?' : '') . $query_params, 'btn btn-sm btn-outline-primary', ['data-target' => '#' . $table_id]);
            }
        }

        return $table;
    }


    /**
     * renders admin grid
     *
     * @param array $elements
     * @param null $header
     * @param BasePage|null $current_page
     * @return string
     * @throws BasicException
     * @throws OutOfRangeException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderAdminGrid(array $elements, int $numCols = 3, ?array $header = null, ?BasePage $current_page = null, string $grid_id = 'listing-grid'): string
    {
        $selectCheckboxes = false;
        foreach ($elements as $elem) {
            if (isset($elem['_admin_table_item_pk'])) {
                $selectCheckboxes = true;
                break;
            }
        }

        if (empty($header)) {
            $header = [];
            foreach ($elements as $key => $elem) {
                $header = array_unique(array_merge($header, array_filter(array_keys($elem), fn ($el) => !str_starts_with($el, '_'))));
            }
            $header = array_flip($header);
        }

        $grid = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'id' => $grid_id,
                'attributes' => ['class' => "container-fluid"],
            ]]
        );

        $searchBox = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'id' => 'searchBox',
                'attributes' => ['class' => "collapse container-fluid bg-light p-3 mb-3"],
            ]]
        );

        $grid->addChild($this->containerMake(TagElement::class, ['options' => [
            'tag' => 'div',
            'attributes' => ['class' => 'row row-cols-2 mb-2'],
            'children' => [
                $this->containerMake(TagElement::class, ['options' => [
                    'tag' => 'div',
                    'attributes' => ['class' => 'col'],
                    'children' => [
                        '<button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#searchBox" role="button" aria-expanded="false" aria-controls="searchBox">'.$this->getUtils()->translate('Search & Sort').'</button>',
                        $searchBox
                    ],
                ]]),
                '<div class="col text-right"><label class="checkbox"><input type="checkbox" id="listing-table-toggle-all" /><span class="checkbox__icon"></span></label>'.$this->getUtils()->translate('Select All').'</div>',
            ],
        ]]));

        // grid elements
        $gridRow = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'attributes' => ['class' => "row row-cols-lg-".$numCols],
            ]]
        );
        $grid->addChild($gridRow);

        if (!empty($elements)) {
            foreach ($elements as $key => $elem) {
                $gridCOlAttributes = ['class' => "col mb-3"];
                if (isset($elem['_admin_table_item_pk'])) {
                    $gridCOlAttributes['data-_item_pk'] = $elem['_admin_table_item_pk'];
                    $gridCOlAttributes['class'] .= ' selectable';
                }
                $dblClicElem = current(array_filter(array_keys($elem['actions'] ?? []), fn($el) => str_ends_with($el, '.dblclick')));
                if ($dblClicElem || isset($elem['actions'][AdminManageModelsPage::EDIT_BTN])) {
                    $linkHtml = $elem['actions'][$dblClicElem ? $dblClicElem : AdminManageModelsPage::EDIT_BTN];
                    if (preg_match('/<a .*?href=["\'](.*?)["\'].*?>/', $linkHtml, $matches)) {
                        $gridCOlAttributes['data-dblclick'] = $matches[1];
                        $gridCOlAttributes['class'] .= ' dblclick';
                    }
                }

                $gridCol = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'div',
                        'attributes' => $gridCOlAttributes,
                    ]]
                );
                $gridRow->addChild($gridCol);

                $card = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'div',
                        'attributes' => ['class' => "card position-relative"],
                    ]]
                );
                $gridCol->addChild($card);

                try {
                    // in a try / catch , as is_callable([$current_page, 'getGridCardBody']) will return true because of the __call method, but will raise an exception
                    $cardBody = ($current_page && is_callable([$current_page, 'getGridCardBody'])) ? $current_page->getGridCardBody($elem, $selectCheckboxes) : $this->getGridCardBody($elem, $selectCheckboxes, $current_page);
                } catch (Exception $e) {
                    $cardBody = $this->getGridCardBody($elem, $selectCheckboxes, $current_page);
                }

                $card->addChild($cardBody);

                if (array_key_exists('actions', $header)) {
                    $card->addChild(
                        $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'div',
                                'text' => is_array($elem['actions']) ? implode(" ", $elem['actions']) : $elem['actions'] ?? '',
                                'attributes' => ['class' => 'card-footer text-muted text-right nowrap'],
                            ]]
                        )
                    );
                }

            }
        } else {
            $text = 'No elements found!';
            if (($current_page instanceof BasePage)) {
                $text = $this->getUtils()->translate($text, locale: $current_page?->getCurrentLocale());
            }

            $gridRow->setAttribute('class', 'row row-cols-1')->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'div',
                        'attributes' => ['class' => 'col text-center'],
                        'text' => $text,
                    ]]
                )
            );
        }

            // searchbox
        $add_searchrow = false;
        if (count($elements) > 0 && $current_page instanceof BasePage) {

            foreach ($header as $k => $v) {
                $className = strtolower($k);
                $th = $k; $column = $v;

                $th = $this->getUtils()->translate($th, locale: $current_page?->getCurrentLocale());

                $request_params = $current_page->getRequest()->query->all();

                if (!empty($column)) {
                    $orderby = null;
                    if (is_array($column)) {
                        if (isset($column['order'])) {
                            $orderby = $column['order'];
                        }
                    } else {
                        $orderby = $column;
                    }
                    if (!empty($orderby)) {
                        $val = 'DESC';
                        if (isset($request_params['order'][$orderby])) {
                            $val = ($request_params['order'][$orderby] == 'ASC') ? 'DESC' : 'ASC';
                        }
                        $request_params['order'][$orderby] = $val;
                        $th = '<a class="ordering" href="' . ($current_page->getControllerUrl() . '?' . http_build_query($request_params)) . '">' . $th . $this->getIcon($val == 'DESC' ? 'arrow-down' : 'arrow-up') . '</a>';
                    }
                }
                
                if ($k == 'actions') {
                    $th = '';
                }


                $div = null;
                if (is_array($v) && isset($v['search']) && boolval($v['search']) == true) {
                    $searchqueryparam = (is_array($current_page->getRequest()->query->all('search')) && isset($current_page->getRequest()->query->all('search')[$v['search']])) ? $current_page->getRequest()->query->all('search')[$v['search']] : '';

                    // locale is a well known special case - but can be overridden
                    if ($v['search'] == 'locale' && ($v['input_type'] ?? 'select') == 'select') {
                        $select_options = ['' => '-- '.$this->getUtils()->translate('All', locale: $current_page->getCurrentLocale()).' --'] + $this->getUtils()->getSiteLanguagesSelectOptions();
                        $select_options = array_map(function ($val, $key) use ($searchqueryparam) {
                            $selected = ($key == $searchqueryparam) ? ' selected="selected"': '';
                            return '<option value="' . $key . '"'.$selected.'>' . $val . '</option>';
                        }, $select_options, array_keys($select_options));
    
                        $div = $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'div',
                                'attributes' => ['class' => 'row row-cols-2 small mb-1 '. $className],
                                'text' => '<div class="col-3 text-nowrap">' . $th . '</div><div class="col"><select class="select-processed" name="search[' . $v['search'] . ']">' . implode("", $select_options) . '</select></div>',
                            ]]
                        );    
                    } else {
                        $div = $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'div',
                                'attributes' => ['class' => 'row row-cols-2 small mb-1 '. $className],
                                'text' => '<div class="col-3 text-nowrap">' . $th . '</div><div class="col"><input class="form-control" name="search[' . $v['search'] . ']" value="' . $searchqueryparam . '"/></div>',
                            ]]
                        );
                    }
                    $add_searchrow = true;
                } else if (is_array($v) && isset($v['foreign']) && boolval($v['foreign']) == true) {
                    $foreignqueryparam = (is_array($current_page->getRequest()->query->all('foreign')) && isset($current_page->getRequest()->query->all('foreign')[$v['foreign']])) ? $current_page->getRequest()->query->all('foreign')[$v['foreign']] : '';

                    $dbtable = $this->getSchema()->getTable($v['table']);
                    $select_options = ['' => '-- '.$this->getUtils()->translate('All', locale: $current_page->getCurrentLocale()).' --'];
                    foreach ($dbtable->getForeignKeys() as $fkobj) {
                        if (in_array($v['foreign'], $fkobj->getColumns())) {
                            $foreign_key = $fkobj->getTargetColumns()[0];
                            $stmt = $this->getDb()->table(
                                $fkobj->getTargetTable()
                            );

                            foreach ($stmt as $row) {
                                $select_options[$row->{$foreign_key}] = $row->{$v['view']};
                            }
                        }
                    }

                    $select_options = array_map(function ($val, $key) use ($foreignqueryparam) {
                        $selected = ($key == $foreignqueryparam) ? ' selected="selected"': '';
                        return '<option value="' . $key . '"'.$selected.'>' . $val . '</option>';
                    }, $select_options, array_keys($select_options));

                    $div = $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'div',
                            'attributes' => ['class' => 'row row-cols-2 small mb-1'],
                            'text' => '<div class="col-3 text-nowrap">' . $th . '</div><div class="col"><select class="select-processed" name="foreign[' . $v['foreign'] . ']">' . implode("", $select_options) . '</select></div>',
                        ]]
                    );

                    $add_searchrow = true;
                } else {
                    if (preg_match("/<a .*?href=/", $th)) {
                        $div = $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'div',
                                'attributes' => ['class' => 'row small mb-1'],
                                'text' => '<div class="col-auto">' . $th . '</div>',
                            ]]
                        );
                        $add_searchrow = true;
                    }
                }

                if ($div) {
                    $searchBox->addChild($div);
                }
            }
        }

        if (($current_page instanceof BasePage)) {
            $request_params = $current_page->getRequest()->query->all();
            if (isset($request_params['order']) || isset($request_params['search'])) {
                $request_params_nosearch = $request_params;
                unset($request_params_nosearch['search']);
                $add_query_parameters = http_build_query($request_params_nosearch);
                if (strlen($add_query_parameters)) {
                    $add_query_parameters = '?' . $add_query_parameters;
                }
                $current_page->addActionLink('reset-btn', 'reset-btn', $this->getUtils()->translate('Reset', locale: $current_page->getCurrentLocale()), $current_page->getControllerUrl() . $add_query_parameters, 'btn btn-sm btn-outline-warning');
            }
            if ($add_searchrow) {
                $query_params = '';
                if (!empty($request_params)) {
                    $query_params = (array)$request_params;
                    unset($query_params['search']);
                    $query_params = http_build_query($query_params);
                }
                $current_page->addActionLink('search-btn', 'search-btn', $this->getIcon('zoom-in') . $this->getUtils()->translate('Search', locale: $current_page->getCurrentLocale()), $current_page->getControllerUrl() . (!empty($query_params) ? '?' : '') . $query_params, 'btn btn-sm btn-outline-primary', ['data-target' => '#' . $grid_id]);
            }
        }

        return $grid;
    }

    protected function getGridCardBody(array $element, bool $selectCheckboxes = false, ?BasePage $current_page = null) : TagElement
    {
        $cardBody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'attributes' => ['class' => "card-body"],
            ]]
        );

        if ($selectCheckboxes == true) {
            if (isset($element['_admin_table_item_pk']) && (
                isset($element['actions'][AdminManageModelsPage::EDIT_BTN]) ||
                isset($element['actions'][AdminManageModelsPage::DELETE_BTN])                        
            )) {
                $cardBody->addChild(
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'label',
                            'text' => '<input class="table-row-selector" type="checkbox" /><span class="checkbox__icon"></span>',
                            'attributes' => ['class' => 'checkbox position-absolute', 'style' => 'top: 10px; right: 10px'],
                        ]]
                    )
                );
            }
        }

        foreach ($element as $tk => $dd) {
            if ($tk == 'actions' || $tk == '_admin_table_item_pk') {
                continue;
            }
            $cardBody->addChild(
                ($dd instanceof TagElement) ? $dd :
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'div',
                            'text' => '<label class="mb-0 mr-2 font-weight-bold">'. $this->getUtils()->translate((string)$tk, locale: $current_page?->getCurrentLocale()) . ':</label>' . (string)$dd,
                            'attributes' => ['class' => in_array(strtolower($tk), ['website', 'locale']) ? 'nowrap' : 'text-break'],
                        ]]
                    )
            );
        }

        return $cardBody;
    }

    /**
     * Renders array as table field name - field value
     * @param array $data
     * @param bool $nowrap
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderArrayOnTable(array $data, bool $nowrap = true): mixed
    {
        $table = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'table',
                'width' => '100%',
                'id' => 'log_table',
                'cellspacing' => '0',
                'cellpadding' => '0',
                'border' => '0',
                'attributes' => ['class' => "table table-striped"],
            ]]
        );

        $thead = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
            ]]
        );
        $tbody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );
        $tfoot = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tfoot',
            ]]
        );

        $table->addChild($thead);

        $row = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
                'attributes' => ['class' => "thead-dark"],
            ]]
        );

        $fields = ['Field Name', 'Field Value'];
        foreach ($fields as $th) {
            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'th',
                        'text' => $th,
                        'scope' => 'col',
                        'attributes' => ($nowrap == true) ? ['class' => 'nowrap'] : [],
                    ]]
                )
            );
        }
        $thead->addChild($row);

        $table->addChild($tbody);

        $counter = 0;
        foreach ($data as $property => $value) {
            $row = $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'tr',
                    'attributes' => ['class' => $counter++ % 2 == 0 ? 'odd' : 'even'],
                ]]
            );

            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'td',
                        'text' => $property,
                        'scope' => 'col',
                        'attributes' => ($nowrap == true) ? ['class' => 'nowrap'] : [],
                    ]]
                )
            );

            if (is_scalar($value)) {
                $row->addChild(
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'text' => $value,
                            'scope' => 'col',
                            'attributes' => ($nowrap == true) ? ['class' => 'nowrap'] : [],
                        ]]
                    )
                );
            } else {
                $row->addChild(
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'text' => "<pre>" . var_export($value, true) . "</pre>",
                            'scope' => 'col',
                            'attributes' => ($nowrap == true) ? ['class' => 'nowrap'] : [],
                        ]]
                    )
                );
            }
            $tbody->addChild($row);
        }

        $table->addChild($tfoot);

        return $table;
    }

    /**
     * renders log
     *
     * @param RequestLog|CronLog|MailLog|AdminActionLog|ProgressManagerProcess $log
     * @param bool $nowrap
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderLog(RequestLog|CronLog|MailLog|AdminActionLog|ProgressManagerProcess $log, bool $nowrap = true): mixed
    {
        $data = [];
        foreach (array_keys($log->getData()) as $property) {
            $handler = [$log, 'get' . $this->getUtils()->snakeCaseToPascalCase($property)];
            $value = call_user_func($handler);

            if (!is_scalar($value)) {
                $value = var_export($value, true);
            }

            if (!empty($value) && str_contains($value, "\\n")) {
                $value = '<pre>'. str_replace("\\n", "\n", $value) . '</pre>';
            }

            $data[$property] = $value;
        }

        return $this->renderArrayOnTable($data, $nowrap);
    }

    /**
     * renders queue message
     *
     * @param QueueMessage $message
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderQueueMessage(QueueMessage $message): mixed
    {
        $data = $message->getMessageData();
        if (isset($data['body'])) {
            $data['body'] = '<div class="code"><code class="html">' . htmlentities($data['body']) . '</code></div>';
            //nl2br(htmlentities($data['body']));
            //highlight_string($data['body'], true);
        }

        return $this->renderArrayOnTable($data, false);
    }

    /**
     * renders application log
     *
     * @param ApplicationLog $log
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderApplicationLog(ApplicationLog $log): mixed
    {
        $data = $log->getData();

        return $this->renderArrayOnTable($data, false);
    }

    /**
     * Get either a Gravatar image tag for a specified email address.
     *
     * @param string $email The email address
     * @param int $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mp | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param string $class html class
     * @return String containing a complete image tag
     */
    public function getGravatar(string $email, int $s = 80, string $d = 'mp', string $r = 'g', string $class = 'rounded-circle'): string
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";

        return $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'img',
            'attributes' => [
                'src' => $url,
                'class' => $class,
                'border' => 0,
            ],
        ]]);
    }

    /**
     * renders a flag icon
     *
     * @param string $country_code
     * @param string $class
     * @param int $width
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function renderFlag(string $country_code, string $class = 'flag-icon', int $width = 20): string
    {
        $filepath = App::getDir(App::FLAGS) . DS . $country_code . '.svg';
        $src = null;
        if (file_exists($filepath)) {
            $src = $this->getAssets()->assetUrl('/flags/' . $country_code . '.svg');
        }

        if (!$src) {
            return "";
        }

        return $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'img',
            'attributes' => [
                'width' => $width,
                'src' => $src,
                'class' => $class,
                'border' => 0,
            ],
        ]]);
    }

    /**
     * gets an icon
     *
     * @param string $icon_name
     * @param array $attributes
     * @return string
     * @throws BasicException
     */
    public function getIcon(string $icon_name, array $attributes = []): string
    {
        return $this->getIcons()->get($icon_name, $attributes, false);
    }

    /**
     * gets an Font Awesome icon
     *
     * @param string $icon_name
     * @param string $theme
     * @return string
     * @throws BasicException
     */
    public function getFAIcon(string $icon_name, string $theme) : string 
    {
        return $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'i',
            'attributes' => [
                'class' => 'fa-' . $theme . ' fa-' . $icon_name,
            ],
        ]]);
    }

    /**
     * returns body_classes array based on controller
     */
    public function getHtmlAdminClasses(AdminPage|Login $controller) : string
    {
        $htmlClasses = [
            'admin-page ' . str_replace('.', '-', $controller->getRouteName())
        ];

        $uiSettings = null;
        if ($controller instanceof AdminPage) {
            $user = $controller->getCurrentUser();
            $uiSettings = $user->getUserSession()->getSessionKey('uiSettings');    

            if ($user->getId() && $controller->hasLoggedUser()) {
                $htmlClasses[] = 'logged-in';
            }    
        }
        $isDarkMode = $uiSettings['darkMode'] ?? $this->getEnvironment()->getVariable('ADMIN_DARK_MODE', false);


        if ($isDarkMode) {
            $htmlClasses[] = 'dark-mode';
        }

        return trim(implode(" ", $htmlClasses));    
    }

    /**
     * return data as qrcode image
     * 
     * @param string $data
     * @param int $imageWidth
     * @return string
     */
    public function getQRCode(string $data, int $imageWidth = 150) : string
    {
        $qrCode = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'img',
                'attributes' => [
                    'class' => '',
                    'src' => (new QRCode)->render($data),
                    'width' => $imageWidth,
                ],
            ]]
        );

        return (string)$qrCode;
    }

    public function renderOrderShipment(OrderShipment $shipment) : string
    {
        /** @var TagElement $table */
        $table = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'table',
                'width' => '100%',
                'cellspacing' => '0',
                'cellpadding' => '0',
                'border' => '0',
                'attributes' => ['class' => "table table-striped"],
            ]]
        );

        $thead = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
            ]]
        );

        $tbody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );

        $table->addChild($thead);
        $table->addChild($tbody);

        $thead->addChild($this->containerMake(
            TagElement::class,
            ['options' =>[
                'tag' => 'tr',
                'attributes' => ['class' => 'thead-dark'],
                'children' => [
                    $this->containerMake(TagElement::class, ['options' => [
                        'tag' => 'th',
                        'text' => $shipment->getShippingMethod() . ($shipment->getStatus() ? " (".$shipment->getStatus().")":""),
                    ]]),
                    $this->containerMake(TagElement::class, ['options' => [
                        'tag' => 'th',
                        'text' => $shipment->getShipmentCode(),
                        'attributes' => ['class' => 'text-right'],
                    ]]),
                ]
            ]]
        ));

        $rownum = 0;
        foreach ($shipment->getItems() as $shipmentItem) {

            /** @var OrderItem $oderItem */
            $orderItem = $shipmentItem->getOrderItem();

            /** @var ProductInterface $product */
            $product = $orderItem->getProduct();

            $tbody->addChild($this->containerMake(
                TagElement::class,
                ['options' =>[
                    'tag' => 'tr',
                    'attributes' => ['class' => $rownum++ % 2 == 0 ? 'odd' : 'even'],
                    'children' => [
                        $this->containerMake(TagElement::class, ['options' => [
                            'tag' => 'td',
                            'text' => '<strong>'.$shipmentItem->getQuantity() .'x</strong> ' .$product->getName() . ' ('. $product->getSku() . ')',
                            'attributes' => ['colspan' => 2, 'class' => 'text-right'],
                        ]]),
                    ]
                ]]
            )); 
        }

        return (string)$table;
    }

    public function renderCalendar(\DateTime $date, string $view = 'month', array $cellsContent = []): string
    {
        switch ($view) {
            case 'day':
                return $this->renderDayView($date, cellsContent: $cellsContent);
            case 'week':
                return $this->renderWeekView($date, cellsContent: $cellsContent);
            case 'month':
            default:
                return $this->renderMonthView($date, cellsContent: $cellsContent);
        }
    }

    protected function renderMonthView(\DateTime $date, array $cellsContent = []): string
    {
        $start = (clone $date)->modify('first day of this month');
        $end   = (clone $date)->modify('last day of this month');

        $startWeekDay = (int)$start->format('N'); // 1 = mon, 7 = sun
        $daysInMonth  = (int)$date->format('t');

        $table = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'table',
                'attributes' => ['class' => "table table-bordered text-center calendar-month"],
            ]]
        );

        $thead = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
            ]]
        );
        $tbody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );

        $table->addChild($thead);
        $table->addChild($tbody);

        $row = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
                'attributes' => ['class' => 'thead-dark'],
            ]]
        );
        foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d) {
            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'th',
                        'text' => substr($this->getUtils()->translate($d), 0, 3),
                    ]]
                )
            );
        }
        $thead->addChild($row);

        $currentDay = 1;
        $currentRow = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
            ]]
        );
        $tbody->addChild($currentRow);

        // empty cells before start
        for ($i=1; $i<$startWeekDay; $i++) {
            $currentRow->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'td',
                        'attributes' => ['class' => 'bg-light'],
                    ]]
                )
            );
        }

        while ($currentDay <= $daysInMonth) {
            $current = (clone $start)->setDate($start->format('Y'), $start->format('m'), $currentDay);

            $class = $this->getCalendarDayClasses($current);

            $currentRow->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'td',
                        'text' => '<div>' . (string)$currentDay . '</div><div>' . ((string)($cellsContent[$current->format('Y-m-d')] ?? '')) . '</div>',
                        'attributes' => ['class' => $class],
                    ]]
                )
            );

            if ($current->format('N') == 7) {
                $currentRow = $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'tr',
                    ]]
                );
                $tbody->addChild($currentRow);
            }

            $currentDay++;
        }

        return (string)$table;
    }

    protected function renderWeekView(\DateTime $date, string $formatDate = 'D d/m', array $cellsContent = []): string
    {
        $startOfWeek = (clone $date)->modify('monday this week');

        $table = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'table',
                'attributes' => ['class' => "table table-bordered text-center calendar-week"],
            ]]
        );

        $thead = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
                'attributes' => ['class' => 'thead-dark'],
            ]]
        );
        $tbody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );
        $table->addChild($thead);
        $table->addChild($tbody);

        $row = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
            ]]
        );
        for ($i=0; $i<7; $i++) {
            $d = (clone $startOfWeek)->modify("+$i day");
            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'th',
                        'text' => $d->format($formatDate),
                    ]]
                )
            );
        }
        $thead->addChild($row);

        $row = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
            ]]
        );
        for ($i=0; $i<7; $i++) {
            $d = (clone $startOfWeek)->modify("+$i day");
            $class = $this->getCalendarDayClasses($d);
            $row->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'td',
                        'text' => (string)($cellsContent[$d->format('Y-m-d')] ?? ''),
                        'attributes' => ['class' => $class, 'style' => 'height:100px'],
                    ]]
                )
            );
        }
        $tbody->addChild($row);
        return (string)$table;
    }

    protected function renderDayView(\DateTime $date, string $formatDate = 'l d F Y', array $cellsContent = []): string
    {
        $table = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'table',
                'attributes' => ['class' => "table table-bordered text-center calendar-day"],
            ]]
        );

        $thead = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
            ]]
        );

        $tbody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );

        $table->addChild($thead);
        $table->addChild($tbody);

        $thead->addChild(
            $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'tr',
                    'attributes' => ['class' => 'thead-dark'],
                    'children' => [
                        $this->containerMake(TagElement::class, ['options' => [
                            'tag' => 'th',
                            'text' => $date->format($formatDate),
                            'attributes' => ['colspan' => 2],
                        ]]),
                    ]
                ]]
            )
        );

        // fasce orarie
        for ($h = 0; $h < 24; $h++) {
            $tbody->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'tr',
                        'children' => [
                            $this->containerMake(TagElement::class, ['options' => [
                                'tag' => 'td',
                                'text' => sprintf('%02d:00', $h),
                                'attributes' => ['style' => 'width:100px;', 'class' => 'fw-bold'],
                            ]]),
                            $this->containerMake(TagElement::class, ['options' => [
                                'tag' => 'td',
                                'text' => (string)($cellsContent[$h] ?? ''),
                                'attributes' => ['style' => 'height:40px;'],
                            ]]),
                        ]
                    ]]
                )
            );
        }

        return (string)$table;
    }

    private function getCalendarDayClasses($current) : string
    {
        $classes = [$current->format('Y-m-d')];
        if ($current->format('Y-m-d') === date('Y-m-d')) {
            $classes[] = 'table-primary fw-bold';
        }
        return implode(' ', $classes);
    }
}
