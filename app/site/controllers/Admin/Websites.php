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
use \App\Site\Models\Website;
use \App\App;

/**
 * "Websites" Admin Page
 */
class Websites extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'websites';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getObjectClass()
    {
        return Website::class;
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
        $website = null;
        if ($this->getRequest()->get('website_id')) {
            $website = $this->loadObject($this->getRequest()->get('website_id'));
        }

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
                // intentional fall-trough
            case 'new':
                $this->addBackButton();
                
                if ($website instanceof Website) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($website->getId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $website_site_name = $website_domain = $website_default_locale = '';
                if ($website instanceof Website) {
                    $website_site_name = $website->site_name;
                    $website_domain = $website->domain;
                    $website_default_locale = $website->default_locale;
                }
                $form->addField('site_name', [
                    'type' => 'textfield',
                    'title' => 'Site Name',
                    'default_value' => $website_site_name,
                    'validate' => ['required'],
                ])
                ->addField('domain', [
                    'type' => 'textfield',
                    'title' => 'Domain',
                    'default_value' => $website_domain,
                    'validate' => ['required'],
                ])
                ->addField('default_locale', [
                    'type' => 'select',
                    'title' => 'Default Locale',
                    'default_value' => $website_default_locale,
                    'options' => $languages,
                    'validate' => ['required'],
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
        /** @var Page $website */
        $website = $this->newEmptyObject();
        if ($this->getRequest()->get('website_id')) {
            $website = $this->loadObject($this->getRequest()->get('website_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $website->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
            case 'edit':
                $website->url = $values['url'];
                $website->title = $values['title'];
                $website->locale = $values['locale'];
                $website->template_name = empty($values['template_name']) ? null : $values['template_name'];
                $website->content = $values['content'];
                $website->meta_keywords = $values['meta_keywords'];
                $website->meta_description = $values['meta_description'];
                $website->html_title = $values['html_title'];
                $website->website_id = $values['website_id'];

                $website->persist();
                break;
            case 'delete':
                $website->delete();
                break;
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
            'Site Name' => 'site_name',
            'Domain' => 'domain',
            'Default Locale' => 'default_locale',
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
        return array_map(function ($website) {

            return [
                'ID' => $website->id,
                'Site Name' => $website->site_name,
                'Domain' => $website->domain,
                'Default Locale' => $website->default_locale,
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&website_id='. $website->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&website_id='. $website->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
            ];
        }, $data);
    }
}
