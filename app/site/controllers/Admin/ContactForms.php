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

use App\Base\Exceptions\PermissionDeniedException;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Abstracts\Base\Element;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Contact;
use App\Site\Models\ContactSubmission;
use App\Site\Controllers\Admin\Json\ContactCallback;
use App\App;

/**
 * "ContactForms" Admin Page
 */
class ContactForms extends AdminManageFrontendModelsPage
{
    /**
     * @var array available form field types
     */
    protected $available_form_field_types = [
        'textfield', 'textarea', 'email', 'number', 'file', 'checkbox',
        'select', 'radios', 'checkboxes', 'range',
        'date', 'datepicker', 'datetime', 'time', 'timeselect',
        'math_captcha', 'image_captcha', 'recaptcha',
    ];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws OutOfRangeException
     * @throws PermissionDeniedException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        AdminFormPage::__construct($container, $request, $route_info);
        $this->page_title = 'Contact Forms';
        if ($this->template_data['action'] == 'list' || $this->template_data['action'] == 'submissions') {
            if ($this->template_data['action'] == 'list') {
                $this->addNewButton();
            } else {
                $this->addBackButton();
            }
            /** @var \App\Base\Abstracts\Models\BaseCollection $collection */
            $collection = $this->containerCall([$this->getObjectClass(), 'getCollection']);
            $collection->addOrder($this->getRequest()->query->get('order'));
            if ($this->template_data['action'] == 'submissions') {
                $collection->addCondition(['contact_id' => $this->getRequest()->get('contact_id')]);
            }
            $data = $this->containerCall([$collection, 'paginate']);

            $this->template_data += [
                'table' => $this->getHtmlRenderer()->renderAdminTable($this->getTableElements($data['items']), $this->getTableHeader(), $this),
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this, $data['page_size']),
            ];
        } elseif ($this->template_data['action'] == 'view_submission') {
            $this->addBackButton();
            $this->template_data += [
                'submission' => $this->containerCall([ContactSubmission::class, 'load'], ['id' => $this->getRequest()->get('submission_id')]),
            ];
        }
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'contact_forms';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_contact';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        if (($this->getRequest()->get('action') ?? 'list') == 'submissions') {
            return ContactSubmission::class;
        }
        return Contact::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        if (($this->getRequest()->get('action') ?? 'list') == 'submissions') {
            return 'submission_id';
        }
        return 'contact_id';
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
            'icon' => 'file-text',
            'text' => 'Contact Forms',
            'section' => 'site',
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
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $contact = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        $contact_title = $contact_template_name = $contact_content = $contact_submit_to = "";
        if ($contact->isLoaded()) {
            $contact_title = $contact->title;
            $contact_content = $contact->content;
            $contact_template_name = $contact->template_name;
            $contact_submit_to = $contact->submit_to;
        }

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $templates = [];
                $initial_dir = App::getDir(App::TEMPLATES) . DS . 'frontend' . DS;
                foreach (glob($initial_dir . 'contacts' . DS . '*.php') as $template) {
                    $key = str_replace($initial_dir, "", $template);
                    $key = preg_replace("/\.php$/i", "", $key);
                    $templates[$key] = basename($template);
                }

                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $contact_title,
                    'validate' => ['required'],
                ])
                ->addField('template_name', [
                    'type' => 'select',
                    'title' => 'Template',
                    'default_value' => $contact_template_name,
                    'options' => ['' => '--'] + $templates,
                ])
                ->addField('content', [
                    'type' => 'tinymce',
                    'title' => 'Content',
                    'tinymce_options' => DEFAULT_TINYMCE_OPTIONS,
                    'default_value' => $contact_content,
                    'rows' => 20,
                ]);

                if ($contact->isLoaded()) {
                    $fieldset = $form->addField('form_definition', [
                        'prefix' => '<div id="fieldset-contactfields"><label class="label-tag_container">Form Components</label>',
                        'suffix' => '</div>',
                        'type' => 'tag_container',
                        'tag' => 'div',
                    ]);

                    $contact_definition = $contact->getContactDefinition();
                    foreach ($contact_definition as $index => $component) {
                        $component['effaceable'] = true;
                        $this->addComponent(
                            $fieldset->addField(
                                'form_component_' . $index . '',
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
                        for ($i = 0; $i < ($num_fields - count($contact_definition)); $i++) {
                            $index = count($contact_definition) + $i;
                            $this->addComponent(
                                $fieldset->addField(
                                    'form_component_' . $index . '',
                                    [
                                        'type' => 'fieldset',
                                        'title' => 'New Field - ' . ($index + 1),
                                        'collapsible' => true,
                                        'collapsed' => true,
                                    ],
                                    $index
                                ),
                                $index
                            );
                        }
                        $fieldset->addJs("\$('input[name=\"num_components\"]','#" . $form->getFormId() . "').val('" . count($form->getField('form_definition')->getFields()) . "');");
                        $fieldset->addJs("\$('select','#" . $form->getFormId() . "').select2({'width':'100%'});");
                    }

                    $form->addField('num_components', [
                        'type' => 'hidden',
                        'default_value' => count($form->getField('form_definition')->getFields()),
                    ]);

                    $form->addField('addmore', [
                        'type' => 'submit',
                        'value' => 'Add more',
                        'ajax_url' => $this->getUrl('crud.app.site.controllers.admin.json.contactcallback') . '?action=' . $this->getRequest()->get('action') . '&contact_id=' . $this->getRequest()->get('contact_id'),
                        'event' => [
                            [
                                'event' => 'click',
                                'callback' => [ContactCallback::class, 'contactFormsCallback'],
                                'target' => 'fieldset-contactfields',
                                'effect' => 'fade',
                                'method' => 'replace',
                            ],
                        ],
                    ]);
                }

                $form->addField('submit_to', [
                    'type' => 'textfield',
                    'title' => 'Submit Results to',
                    'default_value' => $contact_submit_to,
                ]);

                $this->addFrontendFormElements($form, $form_state);
                $this->addSeoFormElements($form, $form_state);
                $this->addSubmitButton($form);

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
     * @param Element $form_component
     * @param int $index
     * @param array|null $component
     * @return Element
     */
    private function addComponent(Element $form_component, int $index, $component = null): Element
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

        $form_component->addField('form_component_' . $index . '[id]', [
            'type' => 'hidden',
            'default_value' => $component['id'],
        ])->addField('form_component_' . $index . '[field_label]', [
            'title' => 'Field Name',
            'type' => 'textfield',
            'default_value' => $component['field_label'],
        ])->addField('form_component_' . $index . '[field_type]', [
            'title' => 'Field Type',
            'type' => 'select',
            'options' => ['' => ''] + array_combine($this->available_form_field_types, array_map('ucfirst', $this->available_form_field_types)),
            'default_value' => $component['field_type'],
            'validate' => ['required'],
        ])->addField('form_component_' . $index . '[field_required]', [
            'title' => 'Required Field',
            'type' => 'select',
            'options' => [0 => 'No', 1 => 'Yes'],
            'default_value' => intval($component['field_required']),
        ])
        ->addField('form_component_' . $index . '[field_data]', [
            'title' => 'Field Data',
            'type' => 'textarea',
            'default_value' => $component['field_data'],
        ]);

        if (isset($component['effaceable']) && $component['effaceable'] == true) {
            $form_component->addField('form_component_' . $index . '[delete]', [
                /*'title' => 'Delete Field',
                'type' => 'checkbox',
                'default_value' => 1,
                'value' => 0,*/

                'type' => 'switchbox',
                'title' => 'Delete',
                'default_value' => 0,
                'yes_value' => 1,
                'yes_label' => 'Yes',
                'no_value' => 0,
                'no_label' => 'No',
                'field_class' => 'switchbox',
                'container_class' => 'col-2 p-0 small',
            ]);
        }

        return $form_component;
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
        $values = $form->values();
        if (!empty((string)$values->submit_to) && is_string($validation = FAPI\Form::validateEmail((string)$values->submit_to))) {
            return str_replace("%t", '"submit to"', $validation);
        }
        // @todo : check if contact language is in contact website languages?

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
         * @var Contact $contact
         */
        $contact = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $contact->setUrl($values['frontend']['url']);
                $contact->setTitle($values['title']);
                $contact->setLocale($values['frontend']['locale']);
                $contact->setTemplateName($values['template_name']);
                $contact->setContent($values['content']);
                $contact->setSubmitTo($values['submit_to']);
                $contact->setWebsiteId($values['frontend']['website_id']);
                $contact->setMetaKeywords($values['seo']['meta_keywords']);
                $contact->setMetaDescription($values['seo']['meta_description']);
                $contact->setHtmlTitle($values['seo']['html_title']);

                $contact->persist();
                if ($values->form_definition) {
                    foreach ($values->form_definition as $key => $row) {
                        $db_row = $this->getDb()->createRow('contact_definition');
                        if (is_numeric($row[$key . '[id]'])) {
                            $db_row = $this->getDb()->table('contact_definition', $row[$key . '[id]']);
                        }

                        $data = [];
                        $data['contact_id'] = $contact->id;
                        $data['field_label'] = $row[$key . '[field_label]'];
                        $data['field_type'] = $row[$key . '[field_type]'];
                        $data['field_required'] = isset($row[$key . '[field_required]']) ? intval($row[$key . '[field_required]']) : 0;
                        $data['field_data'] = $row[$key . '[field_data]'];

                        if ($db_row->exists() && $row[$key . '[delete]'] == '1') {
                            $db_row->delete();
                        } else {
                            $db_row->update($data);
                        }
                    }
                }

                if ($values['action'] == 'new') {
                    $this->addSuccessFlashMessage('Contact Saved. Now you can add components.');
                    return $this->doRedirect($this->getControllerUrl() . '?action=edit&contact_id=' . $contact->id);
                } else {
                    $this->addSuccessFlashMessage("Contact Saved.");
                }

                break;
            case 'delete':
                $contact->delete();
                break;
        }

        return $this->refreshPage();
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        if ($this->template_data['action'] == 'submissions') {
            return [
                'ID' => 'id',
                'User' => 'user_id',
                'Created At' => 'created_at',
                'actions' => null,
            ];
        }

        return [
            'ID' => 'id',
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
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
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function getTableElements(array $data): array
    {
        if ($this->template_data['action'] == 'submissions') {
            return array_map(
                function ($submission) {
                    return [
                        'ID' => $submission->id,
                        'User' => $submission->getUserId() > 0 ? $submission->getOwner()->email : 'guest',
                        'Created At' => $submission->created_at,
                        'actions' => '<a class="btn btn-primary btn-sm" href="' . $this->getControllerUrl() . '?action=view_submission&submission_id=' . $submission->id . '">' . $this->getHtmlRenderer()->getIcon('zoom-in') . '</a>'
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
                    'actions' => implode(
                        " ",
                        [
                            $this->getFrontendModelButton($contact),
                            $this->getTranslationsButton($contact),
                            $this->getActionButton('submissions', $contact->id, 'success', 'list', 'Submissions'),
                            $this->getEditButton($contact->id),
                            $this->getDeleteButton($contact->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
