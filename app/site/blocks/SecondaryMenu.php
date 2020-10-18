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
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Degami\PHPFormsApi as FAPI;
use \Degami\Basics\Html\TagElement;

/**
 * Secondary Menu Block
 */
class SecondaryMenu extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     */
    public function renderHTML(BasePage $current_page = null, $data = [])
    {
        $website_id = $this->getSiteData()->getCurrentWebsiteId();
        $locale = $current_page->getCurrentLocale();
        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        $menu_name = $config['menu_name_' . $locale] ?? '';
        if (empty($menu_name)) {
            return "";
        }

        $tree = $this->getUtils()->getSiteMenu($menu_name, $website_id, $locale);

        $menu_container = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'div',
            'attributes' => [
                'class' => 'menu',
            ],
        ]]);

        $menu_container->addChild($this->_renderSiteMenu($tree));

        $out = $this->getContainer()->make(TagElement::class, ['options' => [
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
     */
    protected function _renderSiteMenu($menu_tree, $parent = null)
    {
        $menu_list = $this->getContainer()->make(TagElement::class, ['options' => [
            'tag' => 'ul',
            'attributes' => [
                'class' => 'navbar-nav mr-auto',
            ],
        ]]);

        foreach ($menu_tree as $leaf) {
            $li = $this->getContainer()->make(TagElement::class, ['options' => [
                'tag' => 'li',
                'attributes' => ['class' => 'menu-item'],
            ]]);
            $menu_list->addChild($li);

            $li->addChild($this->_renderMenuLink($leaf));

            if (isset($leaf['children']) && !empty($leaf['children'])) {
                $li->addChild($this->_renderMenuLink($leaf, 'submenu-elem'));
                $parent_item = $leaf;
                unset($parent_item['children']);
                $li->addChild($this->_renderMenuLink($leaf['children'], $parent_item));
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
     */
    protected function _renderMenuLink($leaf, $link_class = 'menu-elem')
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

    /**
     * additional configuration fieldset
     *
     * @param FAPI\Form $form
     * @param $form_state
     * @param $default_values
     * @return array
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function additionalConfigFieldset(FAPI\Form $form, &$form_state, $default_values)
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
