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
use \App\Site\Models\Taxonomy as TaxonomyModel;
use \App\Site\Models\Page;
use \App\App;

/**
 * "Taxonomy" Admin Page
 */
class Taxonomy extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'taxonomy';
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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $term = null;
        if ($this->getRequest()->get('term_id')) {
            $term = $this->loadObject($this->getRequest()->get('term_id'));
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
                    'pages-btn',
                    'pages-btn',
                    '&#9776; Pages',
                    $this->getUrl('admin.json.termpages', ['id' => $this->getRequest()->get('term_id')]).'?term_id='.$this->getRequest()->get('term_id').'&action=page_assoc',
                    'btn btn-sm btn-light inToolSidePanel'
                );
                // intentional fall trough
                // no break
            case 'new':
                $this->addBackButton();

                $container = $this->getContainer();

                if ($term instanceof TaxonomyModel) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($term->getWebsiteId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $websites = $this->getUtils()->getWebsitesSelectOptions();


                $templates = [];
                $initial_dir = App::getDir(App::TEMPLATES).DS.'frontend'.DS;
                foreach (glob($initial_dir.'terms'.DS.'*.php') as $template) {
                    $key = str_replace($initial_dir, "", $template);
                    $key = preg_replace("/\.php$/i", "", $key);
                    $templates[$key] = basename($template);
                }

                $term_url = $term_locale = $term_title = $term_content = $term_meta_description =
                $term_meta_keywords = $term_template_name = $term_website = $term_html_title = '';
                if ($term instanceof TaxonomyModel) {
                    $term_url = $term->url;
                    $term_locale = $term->locale;
                    $term_title = $term->title;
                    $term_content = $term->content;
                    $term_template_name = $term->template_name;
                    $term_meta_description = $term->meta_description;
                    $term_meta_keywords = $term->meta_keywords;
                    $term_html_title = $term->html_title;
                    $term_website = $term->website_id;
                }

                $form->addField(
                    'url',
                    [
                    'type' => 'textfield',
                    'title' => 'Term url',
                    'default_value' => $term_url,
                    'validate' => ['required'],
                        ]
                )
                    ->addField(
                        'title',
                        [
                        'type' => 'textfield',
                        'title' => 'Title',
                        'default_value' => $term_title,
                        'validate' => ['required'],
                        ]
                    )
                    ->addField(
                        'website_id',
                        [
                        'type' => 'select',
                        'title' => 'Website',
                        'default_value' => $term_website,
                        'options' => $websites,
                        'validate' => ['required'],
                        ]
                    )
                    ->addField(
                        'locale',
                        [
                        'type' => 'select',
                        'title' => 'Locale',
                        'default_value' => $term_locale,
                        'options' => $languages,
                        'validate' => ['required'],
                        ]
                    )
                        ->addField(
                            'meta_description',
                            [
                            'type' => 'textfield',
                            'title' => 'Meta Description',
                            'default_value' => $term_meta_description,
                            ]
                        )
                        ->addField(
                            'meta_keywords',
                            [
                            'type' => 'textfield',
                            'title' => 'Meta Keywords',
                            'default_value' => $term_meta_keywords,
                            ]
                        )
                        ->addField(
                            'html_title',
                            [
                            'type' => 'textfield',
                            'title' => 'Html Title',
                            'default_value' => $term_html_title,
                            ]
                        )
                        ->addField(
                            'template_name',
                            [
                            'type' => 'select',
                            'title' => 'Template',
                            'default_value' => $term_template_name,
                            'options' => ['' => '--' ] + $templates,
                            ]
                        )
                        ->addField(
                            'content',
                            [
                            'type' => 'textarea',
                            'title' => 'Content',
                            'default_value' => $term_content,
                            'rows' => 2,
                            ]
                        )
                        ->addMarkup('<div class="clear"></div>')
                        ->addField(
                            'button',
                            [
                            'type' => 'submit',
                            'value' => 'ok',
                            'container_class' => 'form-item mt-3',
                            'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                            ]
                        );
                if ($this->getRequest()->get('page_id')) {
                    $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                    $form->addField(
                        'page_id',
                        [
                        'type' => 'hidden',
                        'default_value' => $page->id,
                            ]
                    );
                }
                break;
            case 'deassoc':
                $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
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
                        'value' => 'Do you confirm the disassociation of the selected element "'.$term->title.'" from the "'.$page->title.'" page (ID: '.$page->id.') ?',
                        'suffix' => '<br /><br />',
                        ]
                    )
                    ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.pageterms', ['id' => $page->id]).'?page_id='.$page->id.'&action=new">Cancel</a>')
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
                            return ['title' => $page->getTitle(). ' - '.$page->getRewrite()->getUrl(), 'id' => $page->getId()];
                        },
                        $this->getDb()->page()->fetchAll()
                    )
                );

                $pages = array_combine(array_column($pages, 'id'), array_column($pages, 'title'));

                $form
                    ->addField(
                        'page_id',
                        [
                        'type' => 'select',
                        'options' => ['' => ''] + $pages,
                        'default_value' => '',
                        ]
                    )
                    ->addField(
                        'term_id',
                        [
                        'type' => 'hidden',
                        'default_value' => $term->id,
                        ]
                    )
                    ->addField(
                        'button',
                        [
                        'type' => 'submit',
                        'container_tag' => null,
                        'prefix' => '&nbsp;',
                        'value' => 'Ok',
                        'attributes' => ['class' => 'btn btn-primary btn-block btn-lg'],
                        ]
                    );
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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();
        // @todo : check if term language is in term website languages?

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
 * @var TaxonomyModel $term
*/
        $term = $this->newEmptyObject();
        if ($this->getRequest()->get('term_id')) {
            $term = $this->loadObject($this->getRequest()->get('term_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $term->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                $term->url = $values['url'];
                $term->title = $values['title'];
                $term->locale = $values['locale'];
                $term->template_name = empty($values['template_name']) ? null : $values['template_name'];
                $term->content = $values['content'];
                $term->meta_keywords = $values['meta_keywords'];
                $term->meta_description = $values['meta_description'];
                $term->html_title = $values['html_title'];
                $term->website_id = $values['website_id'];
                //$term->parent = $values['parent'];

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
                break;
        }
        if ($this->getRequest()->request->get('page_id') != null) {
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
            'Locale' => 'locale',
            'Title' => 'title',
            'Content' => 'content',
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
            function ($term) {
                return [
                'ID' => $term->id,
                'Website' => $term->getWebsiteId() == null ? 'All websites' : $term->getWebsite()->domain,
                'Locale' => $term->locale,
                'Title' => $term->title,
                'Content' => $term->content,
                'actions' => '<a class="btn btn-light btn-sm" href="'. $term->getFrontendUrl() .'" target="_blank">'.$this->getUtils()->getIcon('zoom-in') .'</a>
                    <a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&term_id='. $term->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&term_id='. $term->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
