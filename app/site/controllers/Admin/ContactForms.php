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
use \App\Base\Abstracts\AdminFormPage;
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Contact;
use \App\Site\Models\ContactSubmission;
use \App\Site\Controllers\Admin\Json\ContactCallback;

/**
 * "ContactForms" Admin Page
 */
class ContactForms extends AdminManageModelsPage
{
    /**
     * @var array available form field types
     */
    protected $available_form_field_types = [
        'textfield','textarea','email','number','file','checkbox',
        'select','radios','checkboxes','range',
        'date', 'datepicker', 'datetime','time','timeselect',
        'math_captcha','image_captcha','recaptcha',
    ];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        AdminFormPage::__construct($container);
        $this->page_title = 'Contact Forms';
        if ($this->templateData['action'] == 'list' || $this->templateData['action'] == 'submissions') {
            if ($this->templateData['action'] == 'list') {
                $this->addNewButton();
            } else {
                $this->addBackButton();
            }
            $data = $this->getContainer()->call(
                [$this->getObjectClass(), 'paginate'],
                ['order' => $this->getRequest()->query->get('order')] +
                (($this->templateData['action'] == 'submissions') ? ['condition' => ['contact_id' => $this->getRequest()->get('contact_id')]] : [])
            );
            
            $this->templateData += [
                'table' => $this->getHtmlRenderer()->renderAdminTable($this->getTableElements($data['items']), $this->getTableHeader(), $this),
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
            ];
        } elseif ($this->templateData['action'] == 'view_submission') {
            $this->addBackButton();
            $this->templateData += [
                'submission' => $this->getContainer()->call([ContactSubmission::class, 'load'], ['id' => $this->getRequest()->get('submission_id')]),
            ];
        }
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'contact_forms';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_contact';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        if (($this->getRequest()->get('action') ?? 'list') == 'submissions') {
            return ContactSubmission::class;
        }
        return Contact::class;
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
        $contact = null;
        if ($this->getRequest()->get('contact_id')) {
            $contact = $this->loadObject($this->getRequest()->get('contact_id'));
        }

        if ($contact instanceof Contact) {
            $languages = $this->getUtils()->getSiteLanguagesSelectOptions($contact->getWebsiteId());
        } else {
            $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
        }

        $websites = $this->getUtils()->getWebsitesSelectOptions();


        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );

        $contact_url = $contact_title = $contact_content = $contact_locale = $contact_submit_to =
        $contact_website = $contact_meta_description = $contact_meta_keywords = $contact_html_title ="";
        if ($contact instanceof Contact) {
            $contact_title = $contact->title;
            $contact_url = $contact->url;
            $contact_content = $contact->content;
            $contact_locale = $contact->locale;
            $contact_submit_to = $contact->submit_to;
            $contact_website = $contact->website_id;
            $contact_meta_description = $contact->meta_keywords;
            $contact_meta_keywords = $contact->meta_description;
            $contact_html_title = $contact->html_title;
        }

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $form->addField(
                    'url',
                    [
                    'type' => 'textfield',
                    'title' => 'Url',
                    'default_value' => $contact_url,
                    'validate' => ['required'],
                    ]
                )->addField(
                    'title',
                    [
                        'type' => 'textfield',
                        'title' => 'Title',
                        'default_value' => $contact_title,
                        'validate' => ['required'],
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
                        'default_value' => $contact_content,
                        'rows' => 20,
                        ]
                    )
                    ->addField(
                        'meta_description',
                        [
                        'type' => 'textfield',
                        'title' => 'Meta Description',
                        'default_value' => $contact_meta_description,
                        ]
                    )
                    ->addField(
                        'meta_keywords',
                        [
                        'type' => 'textfield',
                        'title' => 'Meta Keywords',
                        'default_value' => $contact_meta_keywords,
                        ]
                    )
                    ->addField(
                        'html_title',
                        [
                        'type' => 'textfield',
                        'title' => 'Html Title',
                        'default_value' => $contact_html_title,
                        ]
                    )
                    ->addField(
                        'website_id',
                        [
                        'type' => 'select',
                        'title' => 'Website',
                        'default_value' => $contact_website,
                        'options' => $websites,
                        'validate' => ['required'],
                        ]
                    )
                    ->addField(
                        'locale',
                        [
                        'type' => 'select',
                        'title' => 'Locale',
                        'default_value' => $contact_locale,
                        'options' => $languages,
                        'validate' => ['required'],
                        ]
                    );

                if ($contact instanceof Contact) {
                    $fieldset = $form->addField(
                        'form_definition',
                        [
                        'prefix' => '<div id="fieldset-contactfields"><label class="label-tag_container">Form Components</label>',
                        'suffix' => '</div>',
                        'type' => 'tag_container',
                        'tag' => 'div',
                        ]
                    );

                    $contact_definition = $contact->getContactDefinition();
                    foreach ($contact_definition as $index => $component) {
                            $component['effaceable'] = true;
                            $this->addComponent(
                                $fieldset->addField(
                                    'form_component_'.$index.'',
                                    [
                                    'type' => 'fieldset',
                                    'title' => $component['field_label'],
                                    'collapsible' => true,
                                    'collapsed' => true,
                                    ],
                                    $index
                                ),
                                $index,
                                $component
                            );
                    }
                    if ($form->isPartial() || count($contact_definition) < ($form_state['input_values']['num_components'] ?? 0)) {
                        $num_fields = $form_state['input_values']['num_components'] + ($form->isPartial() ? 1 : 0);
                        for ($i=0; $i < ($num_fields - count($contact_definition)); $i++) {
                            $index = count($contact_definition) + $i;
                            $this->addComponent(
                                $fieldset->addField(
                                    'form_component_'.$index.'',
                                    [
                                    'type' => 'fieldset',
                                    'title' => 'New Field - '.($index + 1),
                                    'collapsible' => true,
                                    'collapsed' => true,
                                    ],
                                    $index
                                ),
                                $index
                            );
                        }
                        $fieldset->addJs("\$('input[name=\"num_components\"]','#".$form->getFormId()."').val('".count($form->getField('form_definition')->getFields())."');");
                        $fieldset->addJs("\$('select','#".$form->getFormId()."').select2({'width':'100%'});");
                    }

                    $form->addField(
                        'num_components',
                        [
                        'type' => 'hidden',
                        'default_value' => count($form->getField('form_definition')->getFields()),
                        ]
                    );

                    $form
                        ->addField(
                            'addmore',
                            [
                            'type' => 'submit',
                            'value' => 'Add more',
                            'ajax_url' => $this->getUrl('admin.json.contactcallback').'?action='.$this->getRequest()->get('action').'&contact_id='.$this->getRequest()->get('contact_id'),
                            'event' => [
                            [
                            'event' => 'click',
                            'callback' => [ContactCallback::class, 'contactFormsCallback'],
                            'target' => 'fieldset-contactfields',
                            'effect' => 'fade',
                            'method' => 'replace',
                            ],
                            ],
                            ]
                        );
                }

                $form
                ->addField(
                    'submit_to',
                    [
                    'type' => 'textfield',
                    'title' => 'Submit Results to',
                    'default_value' => $contact_submit_to,
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
        }

        return $form;
    }

    /**
     * adds a component
     *
     * @param \Degami\PHPFormsApi\Abstracts\Base\Element $form_component
     * @param integer                                    $index
     * @param array                                      $component
     */
    private function addComponent($form_component, $index, $component = null)
    {
        if (is_null($component)) {
            $component = [
                'id' => '',
                'field_label' => '',
                'field_type' => '',
                'field_required' => 0,
                'field_data' => '{"title":"new component"}',
                'effaceable' => false,
            ];
        }

        $form_component
            ->addField(
                'form_component_'.$index.'[id]',
                [
                'type' => 'hidden',
                'default_value' => $component['id'],
                ]
            )
            ->addField(
                'form_component_'.$index.'[field_label]',
                [
                'title' => 'Field Name',
                'type' => 'textfield',
                'default_value' => $component['field_label'],
                ]
            )
            ->addField(
                'form_component_'.$index.'[field_type]',
                [
                'title' => 'Field Type',
                'type' => 'select',
                'options' => ['' => ''] + array_combine($this->available_form_field_types, array_map('ucfirst', $this->available_form_field_types)),
                'default_value' => $component['field_type'],
                'validate' => ['required'],
                ]
            )
            ->addField(
                'form_component_'.$index.'[field_required]',
                [
                'title' => 'Required Field',
                'type' => 'select',
                'options' => [0 => 'No', 1 => 'Yes'],
                'default_value' => intval($component['field_required']),
                ]
            )
            ->addField(
                'form_component_'.$index.'[field_data]',
                [
                'title' => 'Field Data',
                'type' => 'textarea',
                'default_value' => $component['field_data'],
                ]
            );

        if (isset($component['effaceable']) && $component['effaceable'] == true) {
            $form_component
            ->addField(
                'form_component_'.$index.'[delete]',
                [
                'title' => 'Delete Field',
                'type' => 'checkbox',
                'default_value' => '1',
                'value' => '0',
                ]
            );
        }

        return $form_component;
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
        if (!empty((string) $values->submit_to) && is_string($validation = FAPI\Form::validateEmail((string) $values->submit_to))) {
            return str_replace("%t", '"submit to"', $validation);
        }
        // @todo : check if contact language is in contact website languages?

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
         * @var Contact $contact
         */
        $contact = $this->newEmptyObject();
        if ($this->getRequest()->get('contact_id')) {
            $contact = $this->loadObject($this->getRequest()->get('contact_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $contact->url = $values['url'];
                $contact->title = $values['title'];
                $contact->locale = $values['locale'];
                $contact->content = $values['content'];
                $contact->submit_to = $values['submit_to'];
                $contact->website_id = $values['website_id'];

                $contact->persist();
                if ($values->form_definition) {
                    foreach ($values->form_definition as $key => $row) {
                        $db_row = $this->getDb()->createRow('contact_definition');
                        if (is_numeric($row[$key.'[id]'])) {
                            $db_row = $this->getDb()->table('contact_definition', $row[$key.'[id]']);
                        }

                        $data = [];
                        $data['contact_id'] = $contact->id;
                        $data['field_label'] = $row[$key.'[field_label]'];
                        $data['field_type'] = $row[$key.'[field_type]'];
                        $data['field_required'] = isset($row[$key.'[field_required]']) ? intval($row[$key.'[field_required]']) : 0;
                        $data['field_data'] = $row[$key.'[field_data]'];

                        if ($db_row->exists() && $row[$key.'[delete]'] == '1') {
                            $db_row->delete();
                        } else {
                            $db_row->update($data);
                        }
                    }
                }

                if ($values['action'] == 'new') {
                    $this->addFlashMessage('success', 'Contact Saved. Now you can add components.');
                    return $this->doRedirect($this->getControllerUrl().'?action=edit&contact_id='.$contact->id);
                }

                break;
            case 'delete':
                $contact->delete();
                break;
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
        if ($this->templateData['action'] == 'submissions') {
            return [
                'ID' =>'id',
                'User' => 'user_id',
                'Created At' => 'created_at',
                'actions' => null,
            ];
        }

        return [
            'ID' => 'id',
            'Website' => 'website_id',
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'URL' => ['order' => 'url', 'search' => 'url'],
            '# Submissions' => null,
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
        if ($this->templateData['action'] == 'submissions') {
            return array_map(
                function ($submission) {
                    return [
                    'ID' => $submission->id,
                    'User' => $submission->getUserId() > 0 ? $submission->getOwner()->email : 'guest',
                    'Created At' => $submission->created_at,
                    'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=view_submission&submission_id='. $submission->id.'">'.$this->getUtils()->getIcon('zoom-in') .'</a>'
                    ];
                },
                $data
            );
        }

        return array_map(
            function ($contact) {
                return [
                'ID' => $contact->id,
                'Website' => $contact->getWebsiteId() == null ? 'All websites' : $contact->getWebsite()->domain,
                'Title' => $contact->title,
                'Locale' => $contact->locale,
                'URL' => $contact->url,
                '# Submissions' => count($contact->getContactSubmissions()),
                'actions' => '<a class="btn btn-light btn-sm" href="'. $contact->getFrontendUrl() .'" target="_blank">'.$this->getUtils()->getIcon('zoom-in') .'</a>
                    <a class="btn btn-success btn-sm" href="'. $this->getControllerUrl() .'?action=submissions&contact_id='. $contact->id.'">'.$this->getUtils()->getIcon('list') .'</a>
                    <a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&contact_id='. $contact->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&contact_id='. $contact->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
