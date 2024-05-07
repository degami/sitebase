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
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Taxonomy as TaxonomyModel;
use App\Site\Models\Page;
use App\App;

/**
 * "Taxonomy" Admin Page
 */
class Taxonomy extends AdminManageFrontendModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_taxonomy';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return TaxonomyModel::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'term_id';
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
            'icon' => 'list',
            'text' => 'Taxonomy',
            'section' => 'cms',
            'order' => 30,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Exception
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $term = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
                $this->addActionLink(
                    'pages-btn',
                    'pages-btn',
                    '&#9776; Pages',
                    $this->getUrl('crud.app.site.controllers.admin.json.termpages', ['id' => $this->getRequest()->get('term_id')]) . '?term_id=' . $this->getRequest()->get('term_id') . '&action=page_assoc',
                    'btn btn-sm btn-light inToolSidePanel'
                );
            // intentional fall trough
            // no break
            case 'new':
                $this->addBackButton();

                $templates = [];
                $initial_dir = App::getDir(App::TEMPLATES) . DS . 'frontend' . DS;
                foreach (glob($initial_dir . 'terms' . DS . '*.php') as $template) {
                    $key = str_replace($initial_dir, "", $template);
                    $key = preg_replace("/\.php$/i", "", $key);
                    $templates[$key] = basename($template);
                }

                $term_title = $term_content = $term_parent = $term_position = $term_template_name = '';
                if ($term->isLoaded()) {
                    $term_title = $term->title;
                    $term_content = $term->content;
                    $term_template_name = $term->template_name;
                    $term_parent = $term->parent_id;
                    $term_position = $term->position;
                }

                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $term_title,
                    'validate' => ['required'],
                ])->addField('template_name', [
                    'type' => 'select',
                    'title' => 'Template',
                    'default_value' => $term_template_name,
                    'options' => ['' => '--'] + $templates,
                ])->addField('content', [
                    'type' => 'tinymce',
                    'title' => 'Content',
                    'default_value' => $term_content,
                    'rows' => 2,
                ])->addField('position', [
                    'type' => 'number',
                    'title' => 'Position',
                    'default_value' => $term_position,
                    'min' => 0,
                    'max' => 65536, // could be more - db field is unsigned int
                ])->addMarkup('<div class="clear"></div>');

                $this->addFrontendFormElements($form, $form_state);
                $this->addSeoFormElements($form, $form_state);
                $this->addSubmitButton($form);


                if ($this->getRequest()->get('page_id')) {
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                    $form->addField('page_id', [
                        'type' => 'hidden',
                        'default_value' => $page->id,
                    ]);
                }
                break;
            case 'deassoc':
                $page = $this->containerCall([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                $form->addField('page_id', [
                    'type' => 'hidden',
                    'default_value' => $page->id,
                ])->addField('taxonomy_id', [
                    'type' => 'hidden',
                    'default_value' => $term->id,
                ])->addField('confirm', [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the selected element "' . $term->title . '" from the "' . $page->title . '" page (ID: ' . $page->id . ') ?',
                    'suffix' => '<br /><br />',
                ])->addMarkup('<a class="btn btn-danger btn-sm" href="' . $this->getUrl('crud.app.site.controllers.admin.json.pageterms', ['id' => $page->id]) . '?page_id=' . $page->id . '&action=new">Cancel</a>');

                $this->addSubmitButton($form, true);

                break;
            case 'page_assoc':
                $not_in = array_map(
                    function ($el) {
                        return $el->page_id;
                    },
                    $this->getDb()->page_taxonomyList()->where('taxonomy_id', $term->id)->fetchAll()
                );

                $pages = array_filter(
                    array_map(
                        function ($page) use ($not_in) {
                            /** @var Page $page */
                            if (in_array($page->getId(), $not_in)) {
                                return null;
                            }
                            return ['title' => $page->getTitle() . ' - ' . $page->getRewrite()->getUrl(), 'id' => $page->getId()];
                        },
                        Page::getCollection()->getItems()
                    )
                );

                $pages = array_combine(array_column($pages, 'id'), array_column($pages, 'title'));

                $form->addField('page_id', [
                    'type' => 'select',
                    'options' => ['' => ''] + $pages,
                    'default_value' => '',
                ])->addField('term_id', [
                    'type' => 'hidden',
                    'default_value' => $term->id,
                ]);

                $this->addSubmitButton($form, true);
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
        // @todo : check if term language is in term website languages?
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
         * @var TaxonomyModel $term
         */
        $term = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $term->setUserId($this->getCurrentUser()->getId());
            // intentional fall trough
            // no break
            case 'edit':
                $term->setUrl($values['frontend']['url']);
                $term->setTitle($values['title']);
                $term->setLocale($values['frontend']['locale']);
                $term->setTemplateName(empty($values['template_name']) ? null : $values['template_name']);
                $term->setContent($values['content']);
                if (isset($values['seo'])) {
                    $term->setMetaKeywords($values['seo']['meta_keywords']);
                    $term->setMetaDescription($values['seo']['meta_description']);
                    $term->setHtmlTitle($values['seo']['html_title']);
                }
                $term->setWebsiteId($values['frontend']['website_id']);
                //$term->parent_id = $values['parent_id'];
                $term->setPosition($values['position']);

                $this->setAdminActionLogData($term->getChangedData());

                $term->persist();
                if ($values['page_id'] != null) {
                    $this
                        ->getContainer()
                        ->call([Page::class, 'load'], ['id' => $values['page_id']])
                        ->addTerm($term);
                }
                break;
            case 'deassoc':
                if ($values['page_id']) {
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->removeTerm($term);
                }
                break;
            case 'page_assoc':
                if ($values['page_id']) {
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->addTerm($term);
                }
                break;
            case 'delete':
                $term->delete();

                $this->setAdminActionLogData('Deleted term ' . $term->getId());

                break;
        }
        if ($this->getRequest()->request->get('page_id') != null) {
            return JsonResponse::create(['success' => true]);
        }
        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Content' => ['order' => 'content', 'search' => 'content'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($term) {
                return [
                    'ID' => $term->id,
                    'Website' => $term->getWebsiteId() == null ? 'All websites' : $term->getWebsite()->domain,
                    'Locale' => $term->locale,
                    'Title' => $term->title,
                    'Content' => $term->content,
                    'actions' => implode(
                        " ",
                        [
                            $this->getFrontendModelButton($term),
                            $this->getTranslationsButton($term),
                            $this->getEditButton($term->id),
                            $this->getDeleteButton($term->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
