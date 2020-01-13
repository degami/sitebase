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
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Page;
use \App\Site\Models\Taxonomy;
use \App\Site\Models\MediaElement as Media;
use \App\App;

/**
 * "Pages" Admin Page
 */
class Pages extends AdminManageModelsPage
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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $page = null;
        if ($this->getRequest()->get('page_id')) {
            $page = $this->loadObject($this->getRequest()->get('page_id'));
        }

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
                
                if ($page instanceof Page) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($page->getWebsiteId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $templates = [];
                $initial_dir = App::getDir(App::TEMPLATES).DS.'frontend'.DS;
                foreach (glob($initial_dir.'pages'.DS.'*.php') as $template) {
                    $key = str_replace($initial_dir, "", $template);
                    $key = preg_replace("/\.php$/i", "", $key);
                    $templates[$key] = basename($template);
                }

                $page_url = $page_locale = $page_title = $page_content = $page_meta_description =
                $page_meta_keywords = $page_template_name = $page_website = $page_html_title = '';
                if ($page instanceof Page) {
                    $page_url = $page->url;
                    $page_locale = $page->locale;
                    $page_title = $page->title;
                    $page_content = $page->content;
                    $page_template_name = $page->template_name;
                    $page_meta_description = $page->meta_description;
                    $page_meta_keywords = $page->meta_keywords;
                    $page_html_title = $page->html_title;
                    $page_website = $page->website_id;
                }
                $form->addField(
                    'url',
                    [
                    'type' => 'textfield',
                    'title' => 'Page url',
                    'default_value' => $page_url,
                    'validate' => ['required'],
                    ]
                )
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
                    'website_id',
                    [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $page_website,
                    'options' => $websites,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'locale',
                    [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $page_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'meta_description',
                    [
                    'type' => 'textfield',
                    'title' => 'Meta Description',
                    'default_value' => $page_meta_description,
                    ]
                )
                ->addField(
                    'meta_keywords',
                    [
                    'type' => 'textfield',
                    'title' => 'Meta Keywords',
                    'default_value' => $page_meta_keywords,
                    ]
                )
                ->addField(
                    'html_title',
                    [
                    'type' => 'textfield',
                    'title' => 'Html Title',
                    'default_value' => $page_html_title,
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
                )
                ->addField(
                    'button',
                    [
                    'type' => 'submit',
                    'value' => 'ok',
                    'container_class' => 'form-item mt-3',
                    'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                    ]
                );
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
                ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.mediapages', ['id' => $media->id]).'?media_id='.$media->id.'&action=page_assoc">Cancel</a>')
                ->addField(
                    'button',
                    [
                    'type' => 'submit',
                    'container_tag' => null,
                    'prefix' => '&nbsp;',
                    'value' => 'Ok',
                    'attributes' => ['class' => 'btn btn-primary btn-sm'],
                    ]
                );
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
                ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.termpages', ['id' => $term->id]).'?term_id='.$term->id.'&action=page_assoc">Cancel</a>')
                ->addField(
                    'button',
                    [
                    'type' => 'submit',
                    'container_tag' => null,
                    'prefix' => '&nbsp;',
                    'value' => 'Ok',
                    'attributes' => ['class' => 'btn btn-primary btn-sm'],
                    ]
                );

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
        $page = $this->newEmptyObject();
        if ($this->getRequest()->get('page_id')) {
            $page = $this->loadObject($this->getRequest()->get('page_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $page->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                $page->url = $values['url'];
                $page->title = $values['title'];
                $page->locale = $values['locale'];
                $page->template_name = empty($values['template_name']) ? null : $values['template_name'];
                $page->content = $values['content'];
                $page->meta_keywords = $values['meta_keywords'];
                $page->meta_description = $values['meta_description'];
                $page->html_title = $values['html_title'];
                $page->website_id = $values['website_id'];

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
                'actions' => '<a class="btn btn-light btn-sm" href="'. $page->getFrontendUrl() .'" target="_blank">'.$this->getUtils()->getIcon('zoom-in') .'</a>                    
                    <a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&page_id='. $page->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&page_id='. $page->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
