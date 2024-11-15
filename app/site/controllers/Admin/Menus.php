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

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Abstracts\Base\Element;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Menu;
use App\Site\Models\Rewrite;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Menus" Admin Page
 */
class Menus extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'menus';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_menu';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return Menu::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'menu_id';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        if ($this->template_data['action'] == 'list') {
            $this->template_data += [
                'menus' => (array)Menu::allMenusNames($this->getContainer()),
            ];
        }
        return $this->template_data;
    }

    /**
     * {@inheritdocs}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => $this->getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'menu',
            'text' => 'Menu',
            'section' => 'site',
        ];
    }

    /**
     * adds a menu level
     *
     * @param Element $parentFormElement
     * @param string $menu_name
     * @param Menu|null $menuElement
     * @return Element
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function addLevel($parentFormElement, $menu_name, $menuElement = null): Element
    {
        $parent_id = null;
        $thisFormElement = null;
        if ($menuElement instanceof Menu && $menuElement->isLoaded()) {
            $parent_id = $menuElement->id;
            $thisFormElement = $parentFormElement->addField(
                $parentFormElement->getName() . '_menu_id',
                [
                    'type' => 'hidden',
                    'default_value' => $menuElement->id,
                    'container_tag' => '',
                ]
            );
            $parentFormElement
                ->addMarkup($menuElement->title)
                ->addMarkup('<a class="ml-1 btn btn-danger btn-sm float-right" href="' . $this->getControllerUrl() . '?action=delete&menu_id=' . $menuElement->id . '">' . $this->getHtmlRenderer()->getIcon('trash') . '</a>')
                ->addMarkup('<a class="ml-1 btn btn-primary btn-sm float-right" href="' . $this->getControllerUrl() . '?action=edit&menu_id=' . $menuElement->id . '">' . $this->getHtmlRenderer()->getIcon('edit') . '</a>')
                ->addMarkup('<div style="clear:both;"></div>');
        } else {
            $parentFormElement->addField('menu_name', [
                'type' => 'hidden',
                'default_value' => $menu_name,
            ]);
            $thisFormElement = $parentFormElement->addField('menu_item_' . $menu_name, [
                'type' => 'nestable',
                'maxDepth' => 100,
            ])->addMarkup($menu_name);
        }

        foreach (Menu::getCollection()->where(['menu_name' => $menu_name, 'parent_id' => $parent_id], ['position' => 'asc']) as $menu) {
            /** @var Menu $menu */
            $this->addLevel($thisFormElement->addChild(), $menu_name, $menu);
        }

        return $parentFormElement;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $menu = $this->getObject();

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
                    ->addMarkup('<a class="btn btn-link btn-sm" href="' . $this->getControllerUrl() . '">' . $this->getHtmlRenderer()->getIcon('chevron-left') . 'Back</a>');
                $this->addSubmitButton($form, true);
                $form->addMarkup(' <a class="btn btn-success btn-sm" href="' . $this->getControllerUrl() . '?action=new&menu_name=' . $this->getRequest()->get('menu_name') . '">' . $this->getHtmlRenderer()->getIcon('plus') . ' Add new Element</a>');

                break;
            case 'edit':
            case 'new':
                $this->addBackButton();

                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $rewrites = ['' => ''];
                foreach (Rewrite::getCollection() as $rewrite) {
                    $rewrites[$rewrite->id] = $rewrite->route;
                }

                $menu_title = $menu_locale = $menu_menuname = $menu_rewriteid = $menu_parent_id = $menu_position = $menu_href = $menu_target = $menu_breadcrumb = $menu_website = '';
                if ($menu->isLoaded()) {
                    $menu_title = $menu->title;
                    $menu_locale = $menu->locale;
                    $menu_menuname = $menu->menu_name;
                    $menu_rewriteid = $menu->rewrite_id;
                    $menu_href = $menu->href;
                    $menu_target = $menu->target;
                    $menu_parent_id = $menu->parent_id;
                    $menu_position = $menu->position;
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
                ])->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $menu_website,
                    'options' => $websites,
                    'validate' => ['required'],
                ])->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $menu_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ])->addField('menu_name', [
                    'type' => 'textfield',
                    'title' => 'Menu name',
                    'default_value' => $menu_menuname,
                    'validate' => ['required'],
                ])->addField('href', [
                    'type' => 'textfield',
                    'title' => 'Href',
                    'default_value' => $menu_href,
                ])->addField('rewrite_id', [
                    'type' => 'select',
                    'title' => 'Rewrite',
                    'options' => $rewrites,
                    'default_value' => $menu_rewriteid,
                ])->addField('target', [
                    'type' => 'textfield',
                    'title' => 'Target',
                    'default_value' => $menu_target,
                ])->addField('breadcrumb', [
                    'type' => 'textfield',
                    'title' => 'Breadcrumb',
                    'default_value' => $menu_breadcrumb,
                ])->addMarkup('<div class="clear"></div>');
                $this->addSubmitButton($form);
                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        //$values = $form->values();
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var Menu $menu
         */
        $menu = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'view-menu-name':
                $menu_name = $values->menu_name;
                $tree = $values->{'menu_item_' . $menu_name}->_value0->toArray();

                $this->saveLevel($menu_name, $tree, null);
                break;
            case 'new':
            case 'edit':
                $menu->setTitle($values['title']);
                $menu->setWebsiteId($values['website_id']);
                $menu->setLocale($values['locale']);
                $menu->setMenuName($values['menu_name']);
                $menu->setRewriteId(is_numeric($values['rewrite_id']) ? $values['rewrite_id'] : null);
                $menu->setHref($values['href']);
                $menu->setTarget($values['target']);
                //$menu->parent_id = $values['parent_id'];
                //$menu->position = $values['position'];
                $menu->setBreadcrumb($values['breadcrumb']);

                $menu->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Menu Saved."));
                break;
            case 'delete':
                $menu->delete();

                $this->addInfoFlashMessage($this->getUtils()->translate("Menu Deleted."));

                break;
        }

        return $this->refreshPage();
    }

    /**
     * saves level
     *
     * @param string $menu_name
     * @param array $level
     * @param array|null $parent
     * @param int $position
     * @return void
     */
    protected function saveLevel(string $menu_name, array $level, ?array $parent, int $position = 0)
    {
        if (isset($level['children'])) {
            $child_position = 0;
            foreach ($level['children'] as $k => $child) {
                $this->saveLevel($menu_name, $child, $level['value'], $child_position++);
            }
        }
        if (isset($level['value'])) {
            $id = array_pop($level['value']);
            if (is_array($parent)) {
                $parent_id = array_pop($parent);
            } else {
                $parent_id = $parent;
            }
            try {
                $menu_elem = $this->containerCall([Menu::class, 'load'], ['id' => $id]);
                if ($id != null && $menu_elem instanceof Menu) {
                    $menu_elem->setParentId($parent_id);
                    $menu_elem->setPosition($position);
//                $menu_elem->breadcrumb = $menu_elem->getParentIds();
                    $menu_elem->save();
                }
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($menu) {
                return [
                    'Menu Name' => $menu->menu_name,
                    'actions' => '<a class="btn btn-primary btn-sm" href="' . $this->getControllerUrl() . '?action=view-menu-name&menu_name=' . $menu->menu_name . '">' . $this->getHtmlRenderer()->getIcon('zoom-in') . '</a>'
                ];
            },
            $data
        );
    }
}
