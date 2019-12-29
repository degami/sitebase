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
namespace App\Site\Blocks;

use \App\Base\Abstracts\BaseCodeBlock;
use \App\Base\Abstracts\BasePage;
use \Psr\Container\ContainerInterface;
use \App\Site\Models\Menu;
use \App\Base\Traits\AdminTrait;

/**
 * Breadcrumbs Block
 */
class BreadCrumbs extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     * @param  BasePage|null $current_page
     * @return string
     */
    public function renderHTML(BasePage $current_page = null)
    {
        $locale = $current_page->getCurrentLocale();
        $route_info = $current_page->getRouteInfo();

        $current_page_handler = $route_info->getHandler();
        if ($current_page->getRouteGroup() == AdminTrait::getRouteGroup() ||
            $route_info->isAdminRoute() ||
            $current_page_handler[0] == \App\Site\Controllers\Frontend\Page::class && $current_page_handler[1] == 'showFrontPage') {
            return '';
        }

        $menuitems = $this->getContainer()->call([Menu::class, 'where'], ['condition' => ['rewrite_id' => $route_info->getRewrite()]]);
        $menu_item = reset($menuitems);
        $home_url = $this->getRouting()->getUrl('frontend.root');
        $breadcrumbs_links = ['<a href="'.$home_url.'">'.$this->getUtils()->translate('Home', $locale).'</a>'];

        $breadcrumbs_html = '<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">';
        if ($menu_item instanceof Menu) {
            $breadcrumbs = explode('/', $menu_item->getBreadcrumb());
            if (count($breadcrumbs) == 0 || $breadcrumbs[count($breadcrumbs)-1] != $menu_item->getId()) {
                $breadcrumbs[] = $menu_item->getId();
            }

            array_push($breadcrumbs_links, ...array_map(function ($id) {
                $menuItem = $this->getContainer()->call([Menu::class, 'load'], ['id' => $id]);
                $leaf = [
                    'title' => $menuItem->getTitle(),
                    'href' => $menuItem->getLinkUrl(),
                    'target' => $menuItem->getTarget(),
                ];
                return $this->_renderLink($leaf);
            }, array_filter($breadcrumbs)));
        }
        $breadcrumbs_html .= implode('</li><li class="breadcrumb-item">', $breadcrumbs_links);
        $breadcrumbs_html .= '</li></ol></nav>';
        return $breadcrumbs_html;
    }

    /**
     * internally renders menu link
     * @param  array $leaf
     * @param  string $link_class
     * @return string
     */
    protected function _renderLink($leaf, $link_class = 'breadcrumb-link')
    {
        return '<a class="'.$link_class.'" href="'.$leaf['href'].'"'. (($leaf['target']) ? ' target="'.$leaf['target'].'"':'') .'>'.$leaf['title'].'</a>';
    }
}
