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
use \App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\LinkExchange;
use \App\Site\Models\Taxonomy;
use \App\App;

/**
 * "Links" Admin Page
 */
class Links extends AdminManageFrontendModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'links';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_links';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return LinkExchange::class;
    }

   /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'link_id';
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
        $link = $this->getObject();

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
                    'taxonomy-btn',
                    'taxonomy-btn',
                    '&#9776; Terms',
                    $this->getUrl('admin.json.linkterms', ['id' => $this->getRequest()->get('link_id')]).'?link_id='.$this->getRequest()->get('link_id').'&action=new',
                    'btn btn-sm btn-light inToolSidePanel'
                );
                // intentional fall-trough
                // no break
            case 'new':
                $this->addBackButton();

                if ($link->isLoaded()) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($link->getWebsiteId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $link_url = $link_locale = $link_title = $link_description = $link_email = $link_user = $link_website = $link_active = '';
                if ($link->isLoaded()) {
                    $link_url = $link->url;
                    $link_locale = $link->locale;
                    $link_title = $link->title;
                    $link_description = $link->description;
                    $link_email = $link->email;
                    $link_user = $link->user_id;
                    $link_website = $link->website_id;
                    $link_active = $link->active;
                }
                $form->addField(
                    'url',
                    [
                    'type' => 'textfield',
                    'title' => 'Link url',
                    'default_value' => $link_url,
                    'validate' => ['required'],
                        ]
                )
                ->addField(
                    'title',
                    [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $link_title,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'email',
                    [
                    'type' => 'textfield',
                    'title' => 'Email',
                    'default_value' => $link_email,
                    ]
                )
                ->addField(
                    'description',
                    [
                    'type' => 'tinymce',
                    'title' => 'Description',
                    'tinymce_options' => [
                    'plugins' => "code,link,lists,hr,preview,searchreplace,media mediaembed,table,powerpaste",
                    ],
                    'default_value' => $link_description,
                    'rows' => 20,
                    ]
                )
                ->addField(
                    'active',
                    [
                    'type' => 'switchbox',
                    'title' => 'Active',
                    'default_value' => boolval($link_active) ? 1 : 0,
                    'yes_value' => 1,
                    'yes_label' => 'Yes',
                    'no_value' => 0,
                    'no_label' => 'No',
                    'field_class' => 'switchbox',
                    ]
                );


                $this->addFrontendFormElements($form, $form_state, ['website_id','locale']);
                $this->addSubmitButton($form);

                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;

            case 'term_deassoc':
                $term = $this->getContainer()->call([Taxonomy::class, 'load'], ['id' => $this->getRequest()->get('term_id')]);
                $form->addField(
                    'link_id',
                    [
                    'type' => 'hidden',
                    'default_value' => $link->id,
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
                    'value' => 'Do you confirm the disassociation of the "'.$link->title.'"  from the "'.$term->title.'" term (ID: '.$term->id.') ?',
                    'suffix' => '<br /><br />',
                    ]
                )
                ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.termlinks', ['id' => $term->id]).'?term_id='.$term->id.'&action=page_assoc">Cancel</a>');

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
         * @var Page $link
         */
        $link = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $link->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                $link->url = $values['url'];
                $link->title = $values['title'];
                $link->email = $values['email'];
                $link->description = $values['description'];
                $link->active = $values['active'];
                $link->locale = $values['frontend']['locale'];
                $link->website_id = $values['frontend']['website_id'];

                $this->setAdminActionLogData($link->getChangedData());

                $link->persist();
                break;
            case 'delete':
                $link->delete();

                $this->setAdminActionLogData('Deleted link '.$link->getId());

                break;
            case 'term_deassoc':
                if ($values['term_id']) {
                    $term = $this->getContainer()->call([Taxonomy::class, 'load'], ['id' => $values['term_id']]);
                    $link->removeTerm($term);
                }
                break;
        }
        if ($this->getRequest()->request->get('term_id') != null) {
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
            'Active' => 'active',
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
            function ($link) {
                return [
                'ID' => $link->id,
                'Website' => $link->getWebsiteId() == null ? 'All websites' : $link->getWebsite()->domain,
                'URL' => $link->url,
                'Locale' => $link->locale,
                'Title' => $link->title,
                'Active' => $this->getUtils()->translate(boolval($link->active) ? 'Yes' : 'No', $this->getCurrentLocale()),
                'actions' => implode(
                    " ",
                    [
                    $this->getEditButton($link->id),
                    $this->getDeleteButton($link->id),
                    ]
                ),
                ];
            },
            $data
        );
    }
}
