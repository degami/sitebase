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
use \App\Base\Abstracts\Controllers\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Website;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Websites" Admin Page
 */
class Websites extends AdminManageModelsPage
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
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return Website::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'website_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $website = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
                // intentional fall-trough
            case 'new':
                $this->addBackButton();

                if ($website->isLoaded()) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($website->getId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $website_site_name = $website_domain = $website_aliases = $website_default_locale = '';
                if ($website->isLoaded()) {
                    $website_site_name = $website->site_name;
                    $website_domain = $website->domain;
                    $website_aliases = $website->aliases;
                    $website_default_locale = $website->default_locale;
                }
                $form->addField('site_name', [
                    'type' => 'textfield',
                    'title' => 'Site Name',
                    'default_value' => $website_site_name,
                    'validate' => ['required'],
                ])->addField('domain', [
                    'type' => 'textfield',
                    'title' => 'Domain',
                    'default_value' => $website_domain,
                    'validate' => ['required'],
                ])->addField('aliases', [
                    'type' => 'textfield',
                    'title' => 'Aliases',
                    'default_value' => $website_aliases,
                ])->addField('default_locale', [
                    'type' => 'select',
                    'title' => 'Default Locale',
                    'default_value' => $website_default_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ]);

                $this->addSubmitButton($form);
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
         * @var Website $website
         */
        $website = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                // intentional fall trough
                // no break
            case 'edit':
                $website->site_name = $values['site_name'];
                $website->domain = $values['domain'];
                $website->aliases = $values['aliases'];
                $website->default_locale = $values['default_locale'];

                $this->setAdminActionLogData($website->getChangedData());

                $website->persist();
                break;
            case 'delete':
                $website->delete();

                $this->setAdminActionLogData('Deleted website ' . $website->getId());

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
        return [
            'ID' => 'id',
            'Site Name' => ['order' => 'site_name', 'search' => 'site_name'],
            'Domain' => ['order' => 'domain', 'search' => 'domain'],
            'Aliases' => ['order' => null, 'search' => 'aliases'],
            'Default Locale' => ['order' => 'default_locale', 'search' => 'default_locale'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(
            function ($website) {
                return [
                    'ID' => $website->id,
                    'Site Name' => $website->site_name,
                    'Domain' => $website->domain,
                    'Aliases' => $website->aliases,
                    'Default Locale' => $website->default_locale,
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($website->id),
                            $this->getDeleteButton($website->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
