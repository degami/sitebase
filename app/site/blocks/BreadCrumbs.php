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

use \App\Base\Abstracts\Blocks\BaseCodeBlock;
use \App\Base\Abstracts\Controllers\BasePage;
use \Psr\Container\ContainerInterface;
use \App\Site\Models\Menu;
use \App\Base\Traits\AdminTrait;
use \App\Site\Controllers\Frontend\Page;
use \App\Site\Models\Rewrite;
use \Degami\Basics\Html\TagElement;

/**
 * Breadcrumbs Block
 */
class BreadCrumbs extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     *
     * @param  BasePage|null $current_page
     * @return string
     */
    public function renderHTML(BasePage $current_page = null)
    {
        $locale = $current_page->getCurrentLocale();
        $route_info = $current_page->getRouteInfo();

        $current_page_handler = $route_info->getHandler();
        if ($current_page->getRouteGroup() == AdminTrait::getRouteGroup() || $route_info->isAdminRoute()) {
            return '';
        }

        $menuitems = $this->getContainer()->call([Menu::class, 'where'], ['condition' => ['rewrite_id' => $route_info->getRewrite()]]);
        $menu_item = reset($menuitems);
        $home_url = $this->getRouting()->getUrl('frontend.root');

        $breadcrumbs_links = $this->getContainer()->make(TagElement::class, ['options' =>  [
            'tag' => 'ol',
            'attributes' => [
                'class' => 'breadcrumb',
            ],
        ]]);


        $homepageid = $this->getSiteData()->getHomePageId(
            $this->getSiteData()->getCurrentWebsiteId(),
            $current_page->getCurrentLocale()
        );

        if (!$current_page->isHomePage()) {
            $li = $this->getContainer()->make(
                TagElement::class,
                ['options' => [
                'tag' => 'li',
                'attributes' => ['class' => 'breadcrumb-item'],
                ]]
            );

            $atag = $this->getContainer()->make(
                TagElement::class,
                ['options' => [
                    'tag' => 'a',
                    'attributes' => [
                        'class' => 'breadcrumb-link',
                        'href' => $home_url,
                        'title' => (trim($title) != '') ? $this->getUtils()->translate($title, $this->getCurrentLocale()) : '',
                    ],
                    'text' => $this->getUtils()->translate('Home', $locale),
                ]]
            );

            $li->addChild($atag);
            $breadcrumbs_links->addChild($li);
        }

        if ($menu_item instanceof Menu) {
            $breadcrumbs = explode('/', $menu_item->getBreadcrumb());
            if (count($breadcrumbs) == 0 || $breadcrumbs[count($breadcrumbs)-1] != $menu_item->getId()) {
                $breadcrumbs[] = $menu_item->getId();
            }

            foreach (array_map(
                function ($id) use ($homepageid, $locale) {
                    $menuItem = $this->getContainer()->call([Menu::class, 'load'], ['id' => $id]);

                    if ($menuItem->getRewriteId()) {
                        /**
                         * @var Rewrite $rewrite
                         */
                        $rewrite = $this->getContainer()->make(Rewrite::class)->fill($menuItem->getRewriteId());
                        if ($rewrite->getRoute() == '/page/'.$homepageid) {
                            $menuItem->setTitle($this->getUtils()->translate('Home', $locale));
                        }
                    }

                    $leaf = [
                    'title' => $menuItem->getTitle(),
                    'href' => $menuItem->getLinkUrl(),
                    'target' => $menuItem->getTarget(),
                    ];
                    return $this->_renderLink($leaf);
                },
                array_filter($breadcrumbs)
            ) as $atag) {
                $li = $this->getContainer()->make(
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

        $breadcrumbs_container = $this->getContainer()->make(TagElement::class, ['options' =>  [
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
     * @param  array  $leaf
     * @param  string $link_class
     * @return string
     */
    protected function _renderLink($leaf, $link_class = 'breadcrumb-link')
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

        if ($leaf['target']) {
            $link_options['attributes']['target'] = $leaf['target'];
        }

        return $this->getContainer()->make(TagElement::class, ['options' => $link_options]);
    }
}
