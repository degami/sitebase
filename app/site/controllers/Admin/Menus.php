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
namespace App\Site\Controllers\Admin;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Menu;
use \App\Site\Models\Rewrite;
use \App\App;

/**
 * "Menus" Admin Page
 */
class Menus extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'menus';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_menu';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getObjectClass()
    {
        return Menu::class;
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getTemplateData()
    {
        if ($this->templateData['action'] == 'list') {
            $this->templateData += [
                'menus' => (array)Menu::allMenusNames($this->getContainer()),
            ];
        }
        return $this->templateData;
    }


    /**
     * adds a menu level
     * @param \FAPI\Abstracts\App\Base\Element $parentFormElement
     * @param string $menu_name
     * @param Menu|null $menuElement
     */
    private function addLevel(/*FAPI\Abstracts\App\Base\Element*/ $parentFormElement, $menu_name, $menuElement = null)
    {
        $parent_id = null;
        $thisFormElement = null;
        if ($menuElement instanceof Menu) {
            $parent_id = $menuElement->id;
            $thisFormElement = $parentFormElement->addField($parentFormElement->getName().'_menu_id', [
                'type' => 'hidden',
                'default_value' => $menuElement->id,
                'container_tag' => '',
            ]);
            $parentFormElement
                ->addMarkup($menuElement->title)
                ->addMarkup('<a class="ml-1 btn btn-danger btn-sm float-right" href="'.$this->getControllerUrl().'?action=delete&menu_id='.$menuElement->id.'">'.$this->getIcons()->get('trash', [], false).'</a>')
                ->addMarkup('<a class="ml-1 btn btn-primary btn-sm float-right" href="'.$this->getControllerUrl().'?action=edit&menu_id='.$menuElement->id.'">'.$this->getIcons()->get('edit', [], false).'</a>')
                ->addMarkup('<div style="clear:both;"></div>');
        } else {
            $parentFormElement->addField('menu_name', [
                'type' => 'hidden',
                'default_value' => $menu_name,
            ]);
            $thisFormElement = $parentFormElement
            ->addField('menu_item_'.$menu_name, [
                'type' => 'nestable',
                'maxDepth' => 100,
            ])->addMarkup($menu_name);
        }
        
        $menus = $this->getDb()->table('menu')->where('menu_name', $menu_name)->where('parent_id', $parent_id)->fetchAll();
        foreach ($menus as $db_menu) {
            $menu = $this->getContainer()->make(Menu::class, ['dbrow' => $db_menu]);
            $this->addLevel($thisFormElement->addChild(), $menu_name, $menu);
        }

        return $parentFormElement;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $menu = null;
        if ($this->getRequest()->get('menu_id')) {
            $menu = $this->loadObject($this->getRequest()->get('menu_id'));
        }

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'view-menu-name':
                $this->addLevel($form, $this->getRequest()->get('menu_name'), null);

                $form
                ->addMarkup('<div class="clear"></div>')
                ->addMarkup('<hr />')
                ->addMarkup('<a class="btn btn-link btn-sm" href="'.$this->getControllerUrl().'">'.$this->getIcons()->get('chevron-left', [], false).'Back</a>')
                ->addField('button', [
                    'type' => 'button',
                    'container_tag' => null,
                    'prefix' => '&nbsp;',
                    'value' => 'Save',
                    'attributes' => ['class' => 'btn btn-primary btn-sm'],
                ])
                ->addMarkup(' <a class="btn btn-success btn-sm" href="'.$this->getControllerUrl().'?action=new&menu_name='.$this->getRequest()->get('menu_name').'">'.$this->getIcons()->get('plus', [], false).' Add new Element</a>');

                break;
            case 'edit':
            case 'new':
                $this->addBackButton();
            
                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $rewrites = ['' => ''];
                foreach ((array) $this->getContainer()->call([Rewrite::class, 'all']) as $rewrite) {
                    $rewrites[$rewrite->id] = $rewrite->route;
                }

                $menu_title = $menu_locale = $menu_menuname = $menu_rewriteid = $menu_parent = $menu_href = $menu_target = $menu_breadcrumb = $menu_website = '';
                if ($menu instanceof Menu) {
                    $menu_title = $menu->title;
                    $menu_locale = $menu->locale;
                    $menu_menuname = $menu->menu_name;
                    $menu_rewriteid = $menu->rewrite_id;
                    $menu_href = $menu->href;
                    $menu_target = $menu->target;
                    $menu_parent = $menu->parent;
                    $menu_breadcrumb = $menu->breadcrumb;
                    $menu_website = $menu->website_id;
                } elseif ($this->getRequest()->get('menu_name')) {
                    $menu_menuname = $this->getRequest()->get('menu_name');
                }

                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $menu_title,
                    'validate' => ['required'],
                ])
                ->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $menu_website,
                    'options' => $websites,
                    'validate' => ['required'],
                ])
                ->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $menu_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ])
                ->addField('menu_name', [
                    'type' => 'textfield',
                    'title' => 'Menu name',
                    'default_value' => $menu_menuname,
                    'validate' => ['required'],
                ])
                ->addField('href', [
                    'type' => 'textfield',
                    'title' => 'Href',
                    'default_value' => $menu_href,
                ])
                ->addField('rewrite_id', [
                    'type' => 'select',
                    'title' => 'Rewrite',
                    'options' => $rewrites,
                    'default_value' => $menu_rewriteid,
                ])
                ->addField('target', [
                    'type' => 'textfield',
                    'title' => 'Target',
                    'default_value' => $menu_target,
                ])
                ->addField('breadcrumb', [
                    'type' => 'textfield',
                    'title' => 'Breadcrumb',
                    'default_value' => $menu_breadcrumb,
                ])
                ->addMarkup('<div class="clear"></div>')
                ->addField('button', [
                    'type' => 'submit',
                    'value' => 'ok',
                    'container_class' => 'form-item mt-3',
                    'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                ]);
                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        return true;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /** @var Menu $menu */
        $menu = $this->newEmptyObject();
        if ($this->getRequest()->get('menu_id')) {
            $menu = $this->loadObject($this->getRequest()->get('menu_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'view-menu-name':
                $menu_name = $values->menu_name;
                $tree = $values->{'menu_item_'.$menu_name}->_value0->toArray();

                $this->saveLevel($menu_name, $tree, null);
                break;
            case 'new':
            case 'edit':
                $menu->title = $values['title'];
                $menu->website_id = $values['website_id'];
                $menu->locale = $values['locale'];
                $menu->menu_name = $values['menu_name'];
                $menu->rewrite_id = is_numeric($values['rewrite_id']) ? $values['rewrite_id'] : null;
                $menu->href = $values['href'];
                $menu->target = $values['target'];
                //$menu->parent = $values['parent'];
                $menu->breadcrumb = $values['breadcrumb'];

                $menu->persist();
                break;
            case 'delete':
                $menu->delete();
                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * saves level
     * @param  stromg $menu_name
     * @param  array $level
     * @param  array $parent
     * @return void
     */
    protected function saveLevel($menu_name, $level, $parent)
    {
        if (isset($level['children'])) {
            foreach ($level['children'] as $k => $child) {
                $this->saveLevel($menu_name, $child, $level['value']);
            }
        }
        if (isset($level['value'])) {
            $id = array_pop($level['value']);
            if (is_array($parent)) {
                $parent_id = array_pop($parent);
            } else {
                $parent_id = $parent;
            }
            $menu_elem = $this->getContainer()->call([Menu::class, 'load'], ['id' => $id]);
            if ($id != null && $menu_elem instanceof Menu) {
                $menu_elem->parent_id = $parent_id;
                $menu_elem->breadcrumb = $menu_elem->getParentIds();
                $menu_elem->save();
            }
        }
    }

    /**
     * {@inheritdocs}
     * @param array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(function ($menu) {
            return [
                'Menu Name' => $menu->menu_name,
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=view-menu-name&menu_name='. $menu->menu_name.'">'.$this->getUtils()->getIcon('zoom-in') .'</a>'
            ];
        }, $data);
    }
}
