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

namespace App\Site\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Degami\PHPFormsApi as FAPI;
use Degami\Basics\Html\TagElement;

/**
 * Secondary Menu Block
 */
class SecondaryMenu extends BaseCodeBlock
{
    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderHTML(BasePage $current_page = null, $data = []): string
    {
        $website_id = $this->getSiteData()->getCurrentWebsiteId();
        $locale = $current_page?->getCurrentLocale();
        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        $menu_name = $config['menu_name_' . $locale] ?? '';
        if (empty($menu_name)) {
            return "";
        }

        $tree = $this->getSiteData()->getSiteMenu($menu_name, $website_id, $locale);

        $menu_container = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'div',
            'attributes' => [
                'class' => 'menu',
            ],
        ]]);

        $menu_container->addChild($this->renderSiteMenu($tree));

        $out = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'nav',
            'attributes' => [
                'class' => 'secondary-menu',
            ],
        ]]);

        $out->addChild($menu_container);

        return $out;
    }

    /**
     * internally renders menu
     *
     * @param array $menu_tree
     * @param array|null $parent
     * @return string|mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function renderSiteMenu(array $menu_tree, $parent = null): string
    {
        $menu_list = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'ul',
            'attributes' => [
                'class' => 'navbar-nav mr-auto',
            ],
        ]]);

        foreach ($menu_tree as $leaf) {
            $li = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'li',
                'attributes' => ['class' => 'menu-item'],
            ]]);
            $menu_list->addChild($li);

            $li->addChild($this->renderMenuLink($leaf));

            if (isset($leaf['children']) && !empty($leaf['children'])) {
                $li->addChild($this->renderMenuLink($leaf, 'submenu-elem'));
                $parent_item = $leaf;
                unset($parent_item['children']);
                $li->addChild($this->renderMenuLink($leaf['children'], $parent_item));
            }
        }

        return $menu_list;
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
    protected function renderMenuLink(array $leaf, $link_class = 'menu-elem'): string
    {
        if (empty($leaf['title'])) {
            return '';
        }

        $link_options = [
            'tag' => 'a',
            'attributes' => [
                'class' => $link_class,
                'href' => $leaf['href'] ?? '#',
                'title' => $leaf['title'],
            ],
            'text' => $leaf['title'],
        ];

        if ($leaf['target']) {
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
     * @throws BasicException
     * @throws DependencyException
     * @throws FAPI\Exceptions\FormException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function additionalConfigFieldset(FAPI\Form $form, &$form_state, $default_values): array
    {
        $config_fields = [];

        // @todo tabs per website

        $current_website = $this->getSiteData()->getCurrentWebsiteId();
        foreach ($this->getSiteData()->getSiteLocales($current_website) as $locale) {
            $config_fields[] = $form->getFieldObj('menu_name_' . $locale, [
                'type' => 'textfield',
                'title' => $locale . ' menu name',
                'default_value' => $default_values['menu_name_' . $locale] ?? '',
            ]);
        }

        return $config_fields;
    }
}
