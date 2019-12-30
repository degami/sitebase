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
use \App\Site\Models\LinkExchange;
use \App\Site\Models\Taxonomy;
use \App\App;

/**
 * "Links" Admin Page
 */
class Links extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'links';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_links';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getObjectClass()
    {
        return LinkExchange::class;
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
        $link = null;
        if ($this->getRequest()->get('link_id')) {
            $link = $this->loadObject($this->getRequest()->get('link_id'));
        }

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

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
            case 'new':
                $this->addBackButton();

                if ($link instanceof LinkExchange) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($link->getWebsiteId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $link_url = $link_locale = $link_title = $link_description = $link_email = $link_user = $link_website = $link_active = '';
                if ($link instanceof LinkExchange) {
                    $link_url = $link->url;
                    $link_locale = $link->locale;
                    $link_title = $link->title;
                    $link_description = $link->description;
                    $link_email = $link->email;
                    $link_user = $link->user_id;
                    $link_website = $link->website_id;
                    $link_active = $link->active;
                }
                $form->addField('url', [
                    'type' => 'textfield',
                    'title' => 'Link url',
                    'default_value' => $link_url,
                    'validate' => ['required'],
                ])
                ->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $link_title,
                    'validate' => ['required'],
                ])
                ->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $link_website,
                    'options' => $websites,
                    'validate' => ['required'],
                ])
                ->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $link_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ])
                ->addField('email', [
                    'type' => 'textfield',
                    'title' => 'Email',
                    'default_value' => $link_email,
                ])
                ->addField('description', [
                    'type' => 'tinymce',
                    'title' => 'Description',
                    'default_value' => $link_description,
                    'rows' => 20,
                ])
                ->addField('active', [
                    'type' => 'switchbox',
                    'title' => 'Active',
                    'default_value' => boolval($link_active) ? 1 : 0,
                    'yes_value' => 1,
                    'yes_label' => 'Yes',
                    'no_value' => 0,
                    'no_label' => 'No',
                    'field_class' => 'switchbox',
                ])
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

            case 'term_deassoc':
                $term = $this->getContainer()->call([Taxonomy::class, 'load'], ['id' => $this->getRequest()->get('term_id')]);
                $form->addField('link_id', [
                    'type' => 'hidden',
                    'default_value' => $link->id,
                ])
                ->addField('taxonomy_id', [
                    'type' => 'hidden',
                    'default_value' => $term->id,
                ])
                ->addField('confirm', [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the "'.$link->title.'"  from the "'.$term->title.'" term (ID: '.$term->id.') ?',
                    'suffix' => '<br /><br />',
                ])
                ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.termlinks', ['id' => $term->id]).'?term_id='.$term->id.'&action=page_assoc">Cancel</a>')
                ->addField('button', [
                    'type' => 'submit',
                    'container_tag' => null,
                    'prefix' => '&nbsp;',
                    'value' => 'Ok',
                    'attributes' => ['class' => 'btn btn-primary btn-sm'],
                ]);

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
        // @todo : check if page language is in page website languages?
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
        /** @var Page $link */
        $link = $this->newEmptyObject();
        if ($this->getRequest()->get('link_id')) {
            $link = $this->loadObject($this->getRequest()->get('link_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $link->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
            case 'edit':
                $link->url = $values['url'];
                $link->title = $values['title'];
                $link->locale = $values['locale'];
                $link->template_name = empty($values['template_name']) ? null : $values['template_name'];
                $link->content = $values['content'];
                $link->meta_keywords = $values['meta_keywords'];
                $link->meta_description = $values['meta_description'];
                $link->html_title = $values['html_title'];
                $link->website_id = $values['website_id'];

                $link->persist();
                break;
            case 'delete':
                $link->delete();
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
     * @return array
     */
    protected function getTableHeader()
    {
        return [
            'ID' => 'id',
            'Website' => 'website_id',
            'URL' => 'url',
            'Locale' => 'locale',
            'Title' => 'title',
            'Active' => 'active',
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     * @param array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(function ($link) {
            return [
                'ID' => $link->id,
                'Website' => $link->getWebsiteId() == null ? 'All websites' : $link->getWebsite()->domain,
                'URL' => $link->url,
                'Locale' => $link->locale,
                'Title' => $link->title,
                'Active' => $this->getUtils()->translate(boolval($link->active) ? 'Yes' : 'No', $this->getCurrentLocale()),
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&link_id='. $link->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&link_id='. $link->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
            ];
        }, $data);
    }
}