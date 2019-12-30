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

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Request;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\Base\Abstracts\BasePage;
use \App\Base\Abstracts\Model;
use \App\Site\Models\Menu;
use \App\Site\Models\Block;
use \App\Site\Models\Rewrite;
use \App\Site\Models\MailLog;
use \App\Site\Models\RequestLog;
use \App\Site\Models\Website;
use \App\Site\Routing\RouteInfo;
use \App\App;
use \LessQL\Row;
use \Swift_Message;
use \Exception;
use \Degami\PHPFormsApi\Accessories\TagElement;
use \Degami\PHPFormsApi\Accessories\TagList;

/**
 * Html Parts Renderer Helper Class
 */
class HtmlPartsRenderer extends ContainerAwareObject
{
    /**
     * returns flash message html
     * @param  BasePage $controller
     * @return TagList
     */
    public function renderFlashMessages(BasePage $controller)
    {
        $flash_messages = $controller->getFlashMessages();
        $controller->dropFlashMessages();

        $messages_container = $this->getContainer()->make(TagList::class);

        $out = '';
        foreach ((array) $flash_messages as $type => $messages) {
            $messages_list = $this->getContainer()->make(TagList::class);

            foreach ($messages as $message) {
                $messages_list->addChild($this->getContainer()->make(TagElement::class, ['options' => [
                    'tag' => 'div',
                    'text' => $message,
                ]]));
            }

            $messages_container->addChild($this->getContainer()->make(TagElement::class, ['options' => [
                'tag' => 'div',
                'attributes' => ['class' => "alert alert-".$type],
                'text' => (string) $messages_list,
            ]]));
        }

        return $messages_container;
    }

    /**
     * internally renders menu link
     * @param  array $leaf
     * @param  string $link_class
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
            $link_options['id'] = 'navbarDropdown-'.$leaf['menu_id'];
            $link_options['attributes']['role'] = 'button';
            $link_options['attributes']['data-toggle'] = 'dropdown';
            $link_options['attributes']['aria-haspopup'] = 'true';
            $link_options['attributes']['aria-expanded'] = 'false';
        }

        $link = $this->getContainer()->make(TagElement::class, ['options' => $link_options]);
        return $link;
    }

    /**
     * internally renders site menu
     * @param  array $menu_tree
     * @param  array|null $parent
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
            $tag_options['attributes']['aria-labelledby'] = 'navbarDropdown-'.$parent['menu_id'];
        }

        $out = $this->getContainer()->make(TagElement::class, ['options' => $tag_options]);

        if ($parent && $parent['href'] != '#') {
            $out->addChild($this->_renderMenuLink($parent));
        }

        foreach ($menu_tree as $leaf) {
            $leaf_container = ($parent == null) ?
                $this->getContainer()->make(TagElement::class, ['options' => [
                    'tag' => 'li',
                    'attributes' => ['class' => 'nav-item'],
                ]]) :
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
     * @param  string $locale
     * @return string
     */
    public function renderSiteMenu($locale)
    {
        $website_id = $this->getSiteData()->getCurrentWebsite();

        if (empty($locale)) {
            return;
        }

        $menu_name = $this->getSiteData()->getMainMenuName($website_id, $locale);
        if (empty($menu_name)) {
            return;
        }

        $cache_key = strtolower('site.'.$website_id.'.menu.html.'.$locale);
        if ($this->getCache()->has($cache_key)) {
            return $this->getCache()->get($cache_key);
        }

        $menu = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'nav',
            'attributes' => ['class' => 'navbar navbar-expand-lg navbar-light bg-light'],
        ]]);


        if ($this->getSiteData()->getShowLogoOnMenu($website_id)) {
            // add logo
            $menu->addChild($this->getContainer()->make(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'navbar-brand',
                    'href' => $this->getRouting()->getUrl('frontend.root'),
                    'title' => $this->getEnv('APPNAME'),
                ],
                'text' => '<img src="'.$this->getAssets()->assetUrl('/sitebase_logo.png').'" />',
            ]]));
        }

        // add mobile toggle button
        $button = $this->getContainer()->make(TagElement::class, ['options' => [
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
        ]]);
        $button->addChild($this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'span',
            'attributes' => [
                'class' => 'navbar-toggler-icon',
            ],
        ]]));
        $menu->addChild($button);

        // add menu content
        $menu_content = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'div',
            'attributes' => [
                'class' => 'collapse navbar-collapse',
            ],
            'id' => 'navbarSupportedContent',
        ]]);
        $menu_content->addChild($this->_renderSiteMenu($this->getUtils()->getSiteMenu($menu_name, $website_id, $locale)));
        $menu->addChild($menu_content);

        // store into cache
        $this->getCache()->set($cache_key, (string) $menu);
        return (string) $menu;
    }

    /**
     * render region blocks
     * @param  string        $region
     * @param  string        $locale
     * @param  BasePage|null $current_page
     * @return string
     */
    public function renderBlocks($region, $locale = null, BasePage $current_page = null)
    {
        static $pageBlocks = null;
        static $current_rewrite = null;

        $website_id = $this->getSiteData()->getCurrentWebsite();

        $cache_key = strtolower('site.'.$website_id.'.blocks.'.$region.'.html.'.$locale);
        if ($current_page) {
            $route_info = $current_page->getRouteInfo();
            if ($route_info instanceof RouteInfo) {
                $cache_key = strtolower('site.'.$website_id.'.'.trim(str_replace("/", ".", $route_info->getRoute()), '.').'.blocks.'.$region.'.html.'.$locale);

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
     * @param  string $li_class
     * @param  string|null $href
     * @param  string $text
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
            $li->addChild($this->getContainer()->make(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'page-link',
                    'href' => $href,
                ],
                'text' => $text,
            ]]));
        }

        return $li;
    }

    /**
     * renders paginator
     * @param  integer   $current_page
     * @param  integer   $total
     * @param  BasePage  $controller
     * @param  integer   $page_size
     * @param  integer   $visible_links
     * @return string
     */
    public function renderPaginator($current_page, $total, BasePage $controller, $page_size = Model::ITEMS_PER_PAGE, $visible_links = 2)
    {
        $total_pages = ceil($total / $page_size) - 1;
        if ($total_pages < 1) {
            return '';
        }

        $current_base = $controller->getControllerUrl();
        $query_params = $controller->getRequest()->query->all();
        unset($query_params['page']);

        $out = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'nav',
            'attributes' => ['class' => 'd-flex justify-content-end', 'aria-label' => 'Paginator'],
        ]]);

        $ul = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'ul',
            'attributes' => ['class' => 'pagination'],
        ]]);

        $out->addChild($ul);

        // add "first" link
        $ul->addChild($this->getPaginatorLi(
            'page-item'.(($current_page == 0) ? ' disabled':''),
            $current_base.'?'.http_build_query($query_params + ['page' => 0]),
            $this->getUtils()->translate('First', $controller->getCurrentLocale())
        ));

        if ($current_page > 0) {
            // add "previous" link
            $ul->addChild($this->getPaginatorLi(
                'page-item',
                $current_base.'?'.http_build_query($query_params + ['page' =>($current_page-1)]),
                '<span aria-hidden="true">&laquo;</span><span class="sr-only">'.$this->getUtils()->translate('Previous', $controller->getCurrentLocale()).'</span>'
            ));
        }

        if ((max(0, $current_page - $visible_links)) > 0) {
            $ul->addChild($this->getPaginatorLi(
                'page-item disabled',
                null,
                '<span class="page-link">...</span>'
            ));
        }

        for ($i = max(0, $current_page - $visible_links); $i <= min($current_page + $visible_links, $total_pages); $i++) {
            $ul->addChild($this->getPaginatorLi(
                'page-item'.(($current_page == $i) ? ' active':''),
                $current_base.'?'.http_build_query($query_params + ['page' => $i]),
                ($i+1)
            ));
        }

        if ((min($current_page + $visible_links, $total_pages)) < $total_pages) {
            $ul->addChild($this->getPaginatorLi(
                'page-item disabled',
                null,
                '<span class="page-link">...</span>'
            ));
        }

        if ($current_page < $total_pages) {
            // add "next" link
            $ul->addChild($this->getPaginatorLi(
                'page-item',
                $current_base.'?'.http_build_query($query_params + ['page' =>($current_page + 1)]),
                '<span aria-hidden="true">&raquo;</span><span class="sr-only">'.$this->getUtils()->translate('Next', $controller->getCurrentLocale()).'</span>'
            ));
        }

        // add "last" link
        $ul->addChild($this->getPaginatorLi(
            'page-item'.(($current_page == $total_pages) ? ' disabled':''),
            $current_base.'?'.http_build_query($query_params + ['page' => $total_pages]),
            $this->getUtils()->translate('Last', $controller->getCurrentLocale())
        ));

        return (string) $out;
    }

    /**
     * renders admin table
     * @param  array         $elements
     * @param  array|null    $header
     * @param  BasePage|null $current_page
     * @return string
     */
    public function renderAdminTable($elements, $header = null, BasePage $current_page = null)
    {
        $table = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'table',
            'width' => '100%',
            'cellspacing' => '0',
            'cellpadding' => '0',
            'border' => '0',
            'attributes' => ['class' => "table table-striped"],
        ]]);

        $thead = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'thead',
        ]]);
        $tbody = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'tbody',
        ]]);
        $tfoot = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'tfoot',
        ]]);

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

        // tbody
        foreach ($elements as $key => $elem) {
            // ensure all header cols are in row cols
            $elem += array_combine(array_keys($header), array_fill(0, count($header), ''));
            $row = $this->getContainer()->make(TagElement::class, ['options' => [
                'tag' => 'tr',
                'attributes' => ['class' => $key % 2 == 0 ? 'odd' : 'even'],
            ]]);

            foreach ($elem as $tk => $td) {
                if ($tk == 'actions') {
                    continue;
                }
                $row->addChild(
                    ($td instanceof TagElement && $td->getTag() == 'td') ? $td :
                    $this->getContainer()->make(TagElement::class, ['options' => [
                        'tag' => 'td',
                        'text' => (string) $td
                    ]])
                );
            }

            $row->addChild($this->getContainer()->make(TagElement::class, ['options' => [
                'tag' => 'td',
                'text' => $elem['actions'] ?? '',
                'attributes' => ['class' => 'text-right nowrap'],
            ]]));


            $tbody->addChild($row);
        }

        // thead

        $row = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'tr',
            'attributes' => ['class' => "thead-dark"],
        ]]);

        foreach ($header as $th => $column_name) {
            $th = $this->getUtils()->translate($th, $current_page->getCurrentLocale());
            $request_params = [];
            if ($current_page instanceof BasePage) {
                $request_params = $current_page->getRequest()->query->all();

                if (!empty($column_name)) {
                    $val = 'DESC';
                    if (isset($request_params['order'][$column_name])) {
                        $val = ($request_params['order'][$column_name] == 'ASC') ? 'DESC' : 'ASC';
                    }
                    $request_params['order'][$column_name] = $val;
                    $th = '<a class="ordering" href="'.($current_page->getControllerUrl().'?'.http_build_query($request_params)).'">'.$th.$this->getUtils()->getIcon($val == 'DESC' ? 'arrow-down' : 'arrow-up').'</a>';
                }
            }

            if ($th == 'actions') {
                $th = '';
            }
            $row->addChild($this->getContainer()->make(TagElement::class, ['options' => [
                'tag' => 'th',
                'text' => $th,
                'scope' => 'col',
                'attributes' => ['class' => 'nowrap'],
            ]]));
        }
        $thead->addChild($row);

        return $table;
    }

    /**
     * Get either a Gravatar image tag for a specified email address.
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
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

        return (string)(new TagElement([
            'tag' => 'img',
            'attributes' => [
                'src' => $url,
                'class' => $class,
                'border' => 0,
            ],
        ]));
    }

    /**
     * renders a flag icon
     * @param  string  $country_code
     * @param  string  $class
     * @param  integer $width
     * @return string
     */
    public function renderFlag($country_code, $class = 'flag-icon', $width = 20)
    {
        $filepath = App::getDir(App::FLAGS).DS.$country_code.'.svg';
        $src = null;
        if (file_exists($filepath)) {
            $src = $this->getAssets()->assetUrl('/flags/'.$country_code.'.svg');
        }

        if (!$src) {
            return "";
        }

        return (string)(new TagElement([
            'tag' => 'img',
            'attributes' => [
                'width' => $width,
                'src' => $src,
                'class' => $class,
                'border' => 0,
            ],
        ]));
    }
}