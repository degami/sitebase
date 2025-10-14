<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Website;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Websites" Admin Page
 */
class Websites extends AdminManageModelsPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_websites';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return Website::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'website_id';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'upload-cloud',
            'text' => 'Websites',
            'section' => 'system',
            'order' => 3,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';
        $website = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
                // intentional fall-trough
            case 'new':

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
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        //$values = $form->values();
        return true;
    }

    /**
     * {@inheritdoc}
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
         * @var Website $website
         */
        $website = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                // intentional fall trough
                // no break
            case 'edit':
                $website->setSiteName($values['site_name']);
                $website->setDomain($values['domain']);
                $website->setAliases($values['aliases']);
                $website->setDefaultLocale($values['default_locale']);

                $this->setAdminActionLogData($website->getChangedData());

                $website->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Website Saved."));
                break;
            case 'delete':
                $website->delete();

                $this->setAdminActionLogData('Deleted website ' . $website->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Website Deleted."));

                break;
        }
        return $this->refreshPage();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
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
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($website) {
                return [
                    'ID' => $website->id,
                    'Site Name' => $website->site_name,
                    'Domain' => $website->domain,
                    'Aliases' => $website->aliases,
                    'Default Locale' => $website->default_locale,
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($website->id),
                        static::DELETE_BTN => $this->getDeleteButton($website->id),
                    ],
                ];
            },
            $data
        );
    }
}
