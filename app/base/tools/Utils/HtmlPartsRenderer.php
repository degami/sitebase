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

namespace App\Base\Tools\Utils;

use App\Site\Models\Menu;
use App\Site\Models\QueueMessage;
use Degami\Basics\Exceptions\BasicException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Base\Abstracts\Controllers\BasePage;
use \App\Base\Abstracts\Models\BaseModel;
use \App\Site\Models\Rewrite;
use \App\Site\Routing\RouteInfo;
use \App\App;
use \Degami\Basics\Html\TagElement;
use \Degami\Basics\Html\TagList;

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
     */
    public function renderFlashMessages(BasePage $controller)
    {
        $flash_messages = $controller->getFlashMessages();
        $controller->dropFlashMessages();

        $messages_container = $this->getContainer()->make(TagList::class);

        foreach ((array)$flash_messages as $type => $messages) {
            $messages_list = $this->getContainer()->make(TagList::class);

            foreach ($messages as $message) {
                $messages_list->addChild(
                    $this->getContainer()->make(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'div',
                            'text' => $message,
                        ]]
                    )
                );
            }

            $messages_container->addChild(
                $this->getContainer()->make(
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
     */
    protected function _renderMenuLink($leaf, $link_class = 'nav-link')
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
            $link_options['id'] = 'navbarDropdown-' . $leaf['menu_id'];
            $link_options['attributes']['role'] = 'button';
            $link_options['attributes']['data-toggle'] = 'dropdown';
            $link_options['attributes']['aria-haspopup'] = 'true';
            $link_options['attributes']['aria-expanded'] = 'false';
        }

        return $this->getContainer()->make(TagElement::class, ['options' => $link_options]);
    }

    /**
     * internally renders site menu
     *
     * @param array $menu_tree
     * @param array|null $parent
     * @return TagElement
     */
    protected function _renderSiteMenu($menu_tree, $parent = null)
    {
        $tag_options = [
            'tag' => ($parent == null) ? 'ul' : 'div',
            'attributes' => [
                'class' => ($parent == null) ? 'navbar-nav mr-auto' : 'dropdown-menu',
            ],
        ];

        if ($parent != null) {
            $tag_options['attributes']['aria-labelledby'] = 'navbarDropdown-' . $parent['menu_id'];
        }

        $out = $this->getContainer()->make(TagElement::class, ['options' => $tag_options]);

        if ($parent && $parent['href'] != '#') {
            $out->addChild($this->_renderMenuLink($parent));
        }

        foreach ($menu_tree as $leaf) {
            $leaf_container = ($parent == null) ?
                $this->getContainer()->make(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'li',
                        'attributes' => ['class' => 'nav-item'],
                    ]]
                ) :
                $this->getContainer()->make(TagList::class);

            if (isset($leaf['children']) && !empty($leaf['children'])) {
                $leaf_container->addChild($this->_renderMenuLink($leaf, 'nav-link dropdown-toggle'));
                $parent_item = $leaf;
                unset($parent_item['children']);
                $leaf_container->addChild($this->_renderSiteMenu($leaf['children'], $parent_item));
            } else {
                $leaf_container->addChild($this->_renderMenuLink($leaf));
            }

            $out->addChild($leaf_container);
        }

        return $out;
    }

    /**
     * render site menu
     *
     * @param string $locale
     * @return string
     * @throws PhpfastcacheSimpleCacheException
     * @throws BasicException
     */
    public function renderSiteMenu($locale)
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
        $menuitems = $this->getContainer()->call([Menu::class, 'loadMultipleByCondition'], ['condition' => ['menu_name' => $menu_name, 'website_id' => $website_id]]);

        usort($menuitems, function ($a, $b) {
            if ($a->position == $b->position) {
                return 0;
            }
            return ($a->position < $b->position) ? -1 : 1;
        });

        $menu = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'nav',
                'attributes' => ['class' => 'navbar navbar-expand-lg navbar-light bg-light'],
            ]]
        );


        if ($this->getSiteData()->getShowLogoOnMenu($website_id)) {
            // add logo
            $logo = $this->getContainer()->make(
                TagElement::class,
                ['options' => [
                    'tag' => 'img',
                    'attributes' => [
                        'class' => '',
                        'title' => $this->getEnv('APPNAME'),
                        'src' => $this->getAssets()->assetUrl('/sitebase_logo.png'),
                    ],
                ]]
            );

            $atag = $this->getContainer()->make(
                TagElement::class,
                ['options' => [
                    'tag' => 'a',
                    'attributes' => [
                        'class' => 'navbar-brand',
                        'href' => $this->getRouting()->getUrl('frontend.root'),
                        'title' => $this->getEnv('APPNAME'),
                    ],
                ]]
            );

            $atag->addChild($logo);
            $menu->addChild($atag);
        }

        // add mobile toggle button
        $button = $this->getContainer()->make(
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
            $this->getContainer()->make(
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
        $menu_content = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'attributes' => [
                    'class' => 'collapse navbar-collapse',
                ],
                'id' => 'navbarSupportedContent',
            ]]
        );

        $menu_content->addChild($this->_renderSiteMenu($this->getUtils()->buildSiteMenu($menuitems)));
        // $menu_content->addChild($this->_renderSiteMenu($this->getUtils()->getSiteMenu($menu_name, $website_id, $locale)));
        $menu->addChild($menu_content);

        // store into cache
        $this->getCache()->set($cache_key, (string)$menu);
        return (string)$menu;
    }

    /**
     * render region blocks
     *
     * @param string $region
     * @param string|null $locale
     * @param BasePage|null $current_page
     * @return mixed|string|null
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function renderBlocks($region, $locale = null, BasePage $current_page = null)
    {
        static $pageBlocks = null;
        static $current_rewrite = null;

        $website_id = $this->getSiteData()->getCurrentWebsiteId();

        $route_info = null;
        $cache_key = strtolower('site.' . $website_id . '.blocks.' . $region . '.html.' . $locale);
        if ($current_page) {
            if (method_exists($current_page, 'showBlocks') && $current_page->showBlocks() === false) {
                return '';
            }

            $route_info = $current_page->getRouteInfo();
            if ($route_info instanceof RouteInfo) {
                $cache_key = strtolower('site.' . $website_id . '.' . trim(str_replace("/", ".", $route_info->getRoute()), '.') . '.blocks.' . $region . '.html.' . $locale);

                if ($route_info->isAdminRoute()) {
                    $cache_key = null;
                }
            }
        }
        if (!empty($cache_key) && $this->getCache()->has($cache_key)) {
            return $this->getCache()->get($cache_key);
        }

        if ($current_rewrite == null && ($route_info instanceof RouteInfo) && is_numeric($route_info->getRewrite())) {
            $current_rewrite = $this->getContainer()->call([Rewrite::class, 'load'], ['id' => $route_info->getRewrite()]);
        }

        if (is_null($pageBlocks)) {
            $pageBlocks = $this->getUtils()->getAllPageBlocks($locale);
        }

        $out = "";
        if (isset($pageBlocks[$region])) {
            foreach ($pageBlocks[$region] as $block) {
                if ((!$block->isCodeBlock() && $locale != $block->locale) || (!is_null($current_rewrite) && !$block->checkValidRewrite($current_rewrite))) {
                    continue;
                }
                $out .= $block->render($current_page);
            }
        }
        if (!empty($cache_key)) {
            $this->getCache()->set($cache_key, $out);
        }
        return $out;
    }

    /**
     * gets paginator li html tag
     *
     * @param string $li_class
     * @param string|null $href
     * @param string $text
     * @return TagElement
     */
    private function getPaginatorLi($li_class, $href, $text)
    {
        $li_options = [
            'tag' => 'li',
            'attributes' => ['class' => $li_class],
        ];
        if (empty($href)) {
            $li_options['text'] = $text;
        }
        $li = $this->getContainer()->make(TagElement::class, ['options' => $li_options]);

        if (!empty($href)) {
            $li->addChild(
                $this->getContainer()->make(
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
     * @param integer $current_page
     * @param integer $total
     * @param BasePage $controller
     * @param integer $page_size
     * @param integer $visible_links
     * @return string
     * @throws BasicException
     */
    public function renderPaginator($current_page, $total, BasePage $controller, $page_size = BaseModel::ITEMS_PER_PAGE, $visible_links = 2)
    {
        $total_pages = ceil($total / $page_size) - 1;
        if ($total_pages < 1) {
            return '';
        }

        $current_base = $controller->getControllerUrl();
        $query_params = $controller->getRequest()->query->all();
        unset($query_params['page']);

        $out = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'nav',
                'attributes' => ['class' => 'd-flex justify-content-end', 'aria-label' => 'Paginator'],
            ]]
        );

        $ul = $this->getContainer()->make(
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
                $this->getUtils()->translate('First', $controller->getCurrentLocale())
            )
        );

        if ($current_page > 0) {
            // add "previous" link
            $ul->addChild(
                $this->getPaginatorLi(
                    'page-item',
                    $current_base . '?' . http_build_query($query_params + ['page' => ($current_page - 1)]),
                    $this->getContainer()->make(
                        TagList::class
                    )->addChild(
                        $this->getContainer()->make(
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
                        $this->getContainer()->make(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'span',
                                'attributes' => [
                                    'class' => 'sr-only',
                                ],
                                'text' => $this->getUtils()->translate('Previous', $controller->getCurrentLocale()),
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
                    $this->getContainer()->make(
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
                    $this->getContainer()->make(
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
                    $this->getContainer()->make(
                        TagList::class
                    )->addChild(
                        $this->getContainer()->make(
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
                        $this->getContainer()->make(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'span',
                                'attributes' => [
                                    'class' => 'sr-only',
                                ],
                                'text' => $this->getUtils()->translate('Next', $controller->getCurrentLocale()),
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
                $this->getUtils()->translate('Last', $controller->getCurrentLocale())
            )
        );

        return (string)$out;
    }

    /**
     * renders admin table
     *
     * @param array $elements
     * @param array|null $header
     * @param BasePage|null $current_page
     * @return string
     * @throws BasicException
     * @throws OutOfRangeException
     * @throws BasicException
     */
    public function renderAdminTable($elements, $header = null, BasePage $current_page = null)
    {
        $table_id = 'listing-table';


        $table = $this->getContainer()->make(
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

        $thead = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
            ]]
        );
        $tbody = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );
        $tfoot = $this->getContainer()->make(
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
                $header = array_unique(array_merge($header, array_keys($elem)));
            }
            $header = array_flip($header);
        }

        $add_searchrow = false;
        if (count($elements) > 0 && $current_page instanceof BasePage) {
            $search_row = $this->getContainer()->make(
                TagElement::class,
                ['options' => [
                    'tag' => 'tr',
                ]]
            );
            //$style="max-width:100%;font-size: 9px;line-height: 11px;min-width: 100%;padding: 3px 1px;margin: 0;border: 1px solid #555;border-radius: 2px;";
            foreach ($header as $k => $v) {
                if (is_array($v) && isset($v['search']) && boolval($v['search']) == true) {
                    $searchqueryparam = (is_array($current_page->getRequest()->query->get('search')) && isset($current_page->getRequest()->query->get('search')[$v['search']])) ? $current_page->getRequest()->query->get('search')[$v['search']] : '';

                    $td = $this->getContainer()->make(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'attributes' => ['class' => 'small'],
                            'text' => '<input class="form-control" name="search[' . $v['search'] . ']" value="' . $searchqueryparam . '"/>',
                        ]]
                    );
                    $add_searchrow = true;
                } else if (is_array($v) && isset($v['foreign']) && boolval($v['foreign']) == true) {
                    $dbtable = $this->getSchema()->getTable($v['table']);
                    $select_options = [];
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

                    $select_options = array_map(function ($val, $key) {
                        return '<option value="' . $key . '">' . $val . '</option>';
                    }, $select_options, array_keys($select_options));

                    $td = $this->getContainer()->make(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'attributes' => ['class' => 'small'],
                            'text' => '<select name="foreign[' . $v['foreign'] . ']">' . implode("", $select_options) . '</select>',
                        ]]
                    );

                    $add_searchrow = true;
                } else {
                    $td = $this->getContainer()->make(
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
            foreach ($elements as $key => $elem) {
                // ensure all header cols are in row cols
                $elem += array_combine(array_keys($header), array_fill(0, count($header), ''));
                $row = $this->getContainer()->make(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'tr',
                        'attributes' => ['class' => $key % 2 == 0 ? 'odd' : 'even'],
                    ]]
                );

                foreach ($elem as $tk => $td) {
                    if ($tk == 'actions') {
                        continue;
                    }
                    $row->addChild(
                        ($td instanceof TagElement && $td->getTag() == 'td') ? $td :
                            $this->getContainer()->make(
                                TagElement::class,
                                ['options' => [
                                    'tag' => 'td',
                                    'text' => (string)$td
                                ]]
                            )
                    );
                }

                $row->addChild(
                    $this->getContainer()->make(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'text' => $elem['actions'] ?? '',
                            'attributes' => ['class' => 'text-right nowrap'],
                        ]]
                    )
                );


                $tbody->addChild($row);
            }
        } else {
            $text = 'No elements found !';
            if (($current_page instanceof BasePage)) {
                $text = $this->getUtils()->translate($text, $current_page->getCurrentLocale());
            }

            $row = $this->getContainer()->make(
                TagElement::class,
                ['options' => [
                    'tag' => 'tr',
                    'attributes' => ['class' => 'odd'],
                ]]
            );

            $row->addChild(
                $this->getContainer()->make(
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

        $row = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
                'attributes' => ['class' => "thead-dark"],
            ]]
        );

        foreach ($header as $th => $column) {
            $th = $this->getUtils()->translate($th, $current_page->getCurrentLocale());
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
                        $th = '<a class="ordering" href="' . ($current_page->getControllerUrl() . '?' . http_build_query($request_params)) . '">' . $th . $this->getUtils()->getIcon($val == 'DESC' ? 'arrow-down' : 'arrow-up') . '</a>';
                    }
                }
            }

            if ($th == 'actions') {
                $th = '';
            }
            $row->addChild(
                $this->getContainer()->make(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'th',
                        'text' => $th,
                        'scope' => 'col',
                        'attributes' => ['class' => 'nowrap'],
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
                $current_page->addActionLink('reset-btn', 'reset-btn', $this->getUtils()->translate('Reset', $current_page->getCurrentLocale()), $current_page->getControllerUrl() . $add_query_parameters, 'btn btn-sm btn-warning');
            }
            if ($add_searchrow) {
                $query_params = '';
                if (!empty($request_params)) {
                    $query_params = (array)$request_params;
                    unset($query_params['search']);
                    $query_params = http_build_query($query_params);
                }
                $current_page->addActionLink('search-btn', 'search-btn', $this->getUtils()->getIcon('zoom-in') . $this->getUtils()->translate('Search', $current_page->getCurrentLocale()), $current_page->getControllerUrl() . (!empty($query_params) ? '?' : '') . $query_params, 'btn btn-sm btn-primary', ['data-target' => '#' . $table_id]);
            }
        }

        return $table;
    }


    /**
     * Renders array as table field name - field value
     * @param array $data
     * @return mixed
     */
    public function renderArrayOnTable($data)
    {
        $table = $this->getContainer()->make(
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

        $thead = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'thead',
            ]]
        );
        $tbody = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'tbody',
            ]]
        );
        $tfoot = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'tfoot',
            ]]
        );

        $table->addChild($thead);

        $row = $this->getContainer()->make(
            TagElement::class,
            ['options' => [
                'tag' => 'tr',
                'attributes' => ['class' => "thead-dark"],
            ]]
        );

        $fields = ['Field Name', 'Field Value'];
        foreach ($fields as $th) {
            $row->addChild(
                $this->getContainer()->make(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'th',
                        'text' => $th,
                        'scope' => 'col',
                        'attributes' => ['class' => 'nowrap'],
                    ]]
                )
            );
        }
        $thead->addChild($row);

        $table->addChild($tbody);

        $counter = 0;
        foreach ($data as $property => $value) {
            $row = $this->getContainer()->make(
                TagElement::class,
                ['options' => [
                    'tag' => 'tr',
                    'attributes' => ['class' => $counter++ % 2 == 0 ? 'odd' : 'even'],
                ]]
            );

            $row->addChild(
                $this->getContainer()->make(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'td',
                        'text' => $property,
                        'scope' => 'col',
                        'attributes' => ['class' => 'nowrap'],
                    ]]
                )
            );

            if (is_scalar($value)) {
                $row->addChild(
                    $this->getContainer()->make(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'text' => $value,
                            'scope' => 'col',
                            'attributes' => ['class' => 'nowrap'],
                        ]]
                    )
                );
            } else {
                $row->addChild(
                    $this->getContainer()->make(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'td',
                            'text' => "<pre>" . var_export($value, true) . "</pre>",
                            'scope' => 'col',
                            'attributes' => ['class' => 'nowrap'],
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
     * @param $log
     * @return mixed
     * @throws BasicException
     */
    public function renderLog($log)
    {
        $data = [];
        foreach (array_keys($log->getData()) as $property) {
            $handler = [$log, 'get' . $this->getUtils()->snakeCaseToPascalCase($property)];
            $value = call_user_func($handler);

            $data[$property] = $value;
        }

        return $this->renderArrayOnTable($data);
    }

    /**
     * renders queue message
     *
     * @param QueueMessage $message
     * @return mixed
     * @throws BasicException
     */
    public function renderQueueMessage($message)
    {
        $data = $message->getMessageData();

        return $this->renderArrayOnTable($data);
    }

    /**
     * Get either a Gravatar image tag for a specified email address.
     *
     * @param string $email The email address
     * @param integer $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use \[ 404 | mp | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param string $class html class
     * @return String containing a complete image tag
     */
    public function getGravatar($email, $s = 80, $d = 'mp', $r = 'g', $class = 'rounded-circle')
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";

        return (string)(new TagElement(
            [
                'tag' => 'img',
                'attributes' => [
                    'src' => $url,
                    'class' => $class,
                    'border' => 0,
                ],
            ]
        ));
    }

    /**
     * renders a flag icon
     *
     * @param $country_code
     * @param string $class
     * @param int $width
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function renderFlag($country_code, $class = 'flag-icon', $width = 20)
    {
        $filepath = App::getDir(App::FLAGS) . DS . $country_code . '.svg';
        $src = null;
        if (file_exists($filepath)) {
            $src = $this->getAssets()->assetUrl('/flags/' . $country_code . '.svg');
        }

        if (!$src) {
            return "";
        }

        return (string)(new TagElement(
            [
                'tag' => 'img',
                'attributes' => [
                    'width' => $width,
                    'src' => $src,
                    'class' => $class,
                    'border' => 0,
                ],
            ]
        ));
    }
}
