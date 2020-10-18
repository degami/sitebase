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

use Degami\Basics\Exceptions\BasicException;
use Exception;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Taxonomy as TaxonomyModel;
use \App\Site\Models\Page;
use \App\App;

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
    protected function getTemplateName()
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_taxonomy';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return TaxonomyModel::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'term_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
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
                    $this->getUrl('admin.json.termpages', ['id' => $this->getRequest()->get('term_id')]) . '?term_id=' . $this->getRequest()->get('term_id') . '&action=page_assoc',
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
                    $term_position = $term->postion;
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
                    'type' => 'textarea',
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
                    $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                    $form->addField('page_id', [
                        'type' => 'hidden',
                        'default_value' => $page->id,
                    ]);
                }
                break;
            case 'deassoc':
                $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
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
                ])->addMarkup('<a class="btn btn-danger btn-sm" href="' . $this->getUrl('admin.json.pageterms', ['id' => $page->id]) . '?page_id=' . $page->id . '&action=new">Cancel</a>');

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
                        function ($el) use ($not_in) {
                            if (in_array($el->id, $not_in)) {
                                return null;
                            }
                            $page = $this->getContainer()->make(Page::class, ['dbrow' => $el]);
                            return ['title' => $page->getTitle() . ' - ' . $page->getRewrite()->getUrl(), 'id' => $page->getId()];
                        },
                        $this->getDb()->page()->fetchAll()
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
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
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
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /**
         * @var TaxonomyModel $term
         */
        $term = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $term->user_id = $this->getCurrentUser()->id;
            // intentional fall trough
            // no break
            case 'edit':
                $term->url = $values['frontend']['url'];
                $term->title = $values['title'];
                $term->locale = $values['frontend']['locale'];
                $term->template_name = empty($values['template_name']) ? null : $values['template_name'];
                $term->content = $values['content'];
                if (isset($values['seo'])) {
                    $term->meta_keywords = $values['seo']['meta_keywords'];
                    $term->meta_description = $values['seo']['meta_description'];
                    $term->html_title = $values['seo']['html_title'];
                }
                $term->website_id = $values['frontend']['website_id'];
                //$term->parent_id = $values['parent_id'];
                $term->position = $values['position'];

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
                    $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->removeTerm($term);
                }
                break;
            case 'page_assoc':
                if ($values['page_id']) {
                    $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $values['page_id']]);
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
    protected function getTableHeader()
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
    protected function getTableElements($data)
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
