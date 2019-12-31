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
use \Degami\PHPFormsApi as FAPI;

/**
 * Secondary Menu Block
 */
class SecondaryMenu extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     *
     * @param  BasePage|null $current_page
     * @param  array         $data
     * @return string
     */
    public function renderHTML(BasePage $current_page = null, $data = [])
    {
        $locale = $current_page->getCurrentLocale();
        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        $menu_name = $config['menu_name_'.$locale] ?? '';
        if (empty($menu_name)) {
            return "";
        }

        return '<nav class="secondary-menu">
          <div class="menu">' .$this->_renderSiteMenu($this->getUtils()->getSiteMenu($menu_name, $website_id, $locale)). '</div>
        </nav>';
    }

    /**
     * internally renders menu
     *
     * @param  array $menu_tree
     * @param  array $parent
     * @return string
     */
    protected function _renderSiteMenu($menu_tree, $parent = null)
    {
        $out = '<ul class="navbar-nav mr-auto">';
        foreach ($menu_tree as $leaf) {
            $out .= '<li class="menu-item">';
            $out .= $this->_renderMenuLink($leaf);

            if (isset($leaf['children']) && !empty($leaf['children'])) {
                $out .= $this->_renderMenuLink($leaf, 'submenu-elem');
                $parent_item = $leaf;
                unset($parent_item['children']);
                $out .= $this->_renderSiteMenu($leaf['children'], $parent_item);
            }
            $out .= '</li>';
        }
        $out .= '</ul>';

        return $out;
    }

    /**
     * internally renders menu link
     *
     * @param  array  $leaf
     * @param  string $link_class
     * @return string
     */
    protected function _renderMenuLink($leaf, $link_class = 'menu-elem')
    {
        return '<a class="'.$link_class.'" href="'.$leaf['href'].'"'.
                    (($leaf['target']) ? ' target="'.$leaf['target'].'"':'') .'>'.$leaf['title'].'</a>';
    }

    /**
     * additional configuration fieldset
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @param  array     $default_values
     * @return FAPI\Form
     */
    public function additionalConfigFieldset(FAPI\Form $form, &$form_state, $default_values)
    {
        $config_fields = [];

        // @todo tabs per website

        $current_website = $this->getSiteData()->getCurrentWebsiteId();
        foreach ($this->getSiteData()->getSiteLocales($current_website) as $locale) {
            $config_fields[] = $form->getFieldObj(
                'menu_name_'.$locale,
                [
                'type' => 'textfield',
                'title' => $locale.' menu name',
                'default_value' => $default_values['menu_name_'.$locale] ?? '',
                ]
            );
        }

        return $config_fields;
    }
}
