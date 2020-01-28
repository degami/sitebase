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
use \Symfony\Component\HttpFoundation\JsonResponse;
use \App\Base\Abstracts\AdminManageFrontendModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Page;
use \App\Site\Models\Taxonomy;
use \App\Site\Models\MediaElement as Media;
use \App\App;

/**
 * "Pages" Admin Page
 */
class Pages extends AdminManageFrontendModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'pages';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_pages';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return Page::class;
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'page_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $page = $this->getObject();

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );

        switch ($type) {
            case 'edit':
                $this->addActionLink(
                    'media-btn',
                    'media-btn',
                    '&#9776; Media',
                    $this->getUrl('admin.json.pagemedia', ['id' => $this->getRequest()->get('page_id')]).'?page_id='.$this->getRequest()->get('page_id').'&action=new',
                    'btn btn-sm btn-light inToolSidePanel'
                );
                $this->addActionLink(
                    'taxonomy-btn',
                    'taxonomy-btn',
                    '&#9776; Terms',
                    $this->getUrl('admin.json.pageterms', ['id' => $this->getRequest()->get('page_id')]).'?page_id='.$this->getRequest()->get('page_id').'&action=new',
                    'btn btn-sm btn-light inToolSidePanel'
                );
                // intentional fall-trough
                // no break
            case 'new':
                $this->addBackButton();

                $templates = [];
                $initial_dir = App::getDir(App::TEMPLATES).DS.'frontend'.DS;
                foreach (glob($initial_dir.'pages'.DS.'*.php') as $template) {
                    $key = str_replace($initial_dir, "", $template);
                    $key = preg_replace("/\.php$/i", "", $key);
                    $templates[$key] = basename($template);
                }

                $page_title = $page_content = $page_template_name = '';
                if ($page->isLoaded()) {
                    $page_title = $page->title;
                    $page_content = $page->content;
                    $page_template_name = $page->template_name;
                }
                $form
                ->addField(
                    'title',
                    [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $page_title,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'template_name',
                    [
                    'type' => 'select',
                    'title' => 'Template',
                    'default_value' => $page_template_name,
                    'options' => ['' => '--' ] + $templates,
                    ]
                )
                ->addField(
                    'content',
                    [
                    'type' => 'tinymce',
                    'title' => 'Content',
                    'tinymce_options' => [
                    'plugins' => "code,link,lists,hr,preview,searchreplace,media mediaembed,table,powerpaste",
                    ],
                    'default_value' => $page_content,
                    'rows' => 20,
                    ]
                );

                $this->addFrontendFormElements($form, $form_state);
                $this->addSeoFormElements($form, $form_state);
                $this->addSubmitButton($form);

                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;

            case 'media_deassoc':
                $media = $this->getContainer()->call([Media::class, 'load'], ['id' => $this->getRequest()->get('media_id')]);
                $form->addField(
                    'page_id',
                    [
                    'type' => 'hidden',
                    'default_value' => $page->id,
                    ]
                )
                ->addField(
                    'media_id',
                    [
                    'type' => 'hidden',
                    'default_value' => $media->id,
                    ]
                )
                ->addField(
                    'confirm',
                    [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the "'.$page->title.'" page from the media ID: '.$media->id.'?',
                    'suffix' => '<br /><br />',
                    ]
                )
                ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.mediapages', ['id' => $media->id]).'?media_id='.$media->id.'&action=page_assoc">Cancel</a>');

                $this->addSubmitButton($form, true);
                break;

            case 'term_deassoc':
                $term = $this->getContainer()->call([Taxonomy::class, 'load'], ['id' => $this->getRequest()->get('term_id')]);
                $form->addField(
                    'page_id',
                    [
                    'type' => 'hidden',
                    'default_value' => $page->id,
                    ]
                )
                ->addField(
                    'taxonomy_id',
                    [
                    'type' => 'hidden',
                    'default_value' => $term->id,
                    ]
                )
                ->addField(
                    'confirm',
                    [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the "'.$page->title.'"  from the "'.$term->title.'" term (ID: '.$term->id.') ?',
                    'suffix' => '<br /><br />',
                    ]
                )
                ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.termpages', ['id' => $term->id]).'?term_id='.$term->id.'&action=page_assoc">Cancel</a>');

                $this->addSubmitButton($form, true);

                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();
        // @todo : check if page language is in page website languages?
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /**
         * @var Page $page
         */
        $page = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':
                $page->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                $page->url = $values['frontend']['url'];
                $page->title = $values['title'];
                $page->locale = $values['frontend']['locale'];
                $page->template_name = empty($values['template_name']) ? null : $values['template_name'];
                $page->content = $values['content'];
                $page->meta_keywords = $values['seo']['meta_keywords'];
                $page->meta_description = $values['seo']['meta_description'];
                $page->html_title = $values['seo']['html_title'];
                $page->website_id = $values['frontend']['website_id'];

                $page->persist();
                break;
            case 'delete':
                $page->delete();
                break;
            case 'media_deassoc':
                if ($values['media_id']) {
                    $media = $this->getContainer()->call([Media::class, 'load'], ['id' => $values['media_id']]);
                    $page->removeMedia($media);
                }
                break;
            case 'term_deassoc':
                if ($values['term_id']) {
                    $term = $this->getContainer()->call([Taxonomy::class, 'load'], ['id' => $values['term_id']]);
                    $page->removeTerm($term);
                }
                break;
        }
        if ($this->getRequest()->request->get('media_id') != null || $this->getRequest()->request->get('term_id') != null) {
            return JsonResponse::create(['success'=>true]);
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
            'Website' => 'website_id',
            'URL' => ['order' => 'url', 'search' => 'url'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param  array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(
            function ($page) {
                return [
                'ID' => $page->id,
                'Website' => $page->getWebsiteId() == null ? 'All websites' : $page->getWebsite()->domain,
                'URL' => $page->url,
                'Locale' => $page->locale,
                'Title' => $page->title,
                'actions' => implode(
                    " ",
                    [
                    $this->getFrontendModelButton($page),
                    $this->getEditButton($page->id),
                    $this->getDeleteButton($page->id),
                    ]
                ),
                ];
            },
            $data
        );
    }
}
