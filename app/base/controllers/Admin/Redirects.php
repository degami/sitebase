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
use App\Base\Models\Redirect;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Redirects" Admin Page
 */
class Redirects extends AdminManageModelsPage
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
        return 'administer_rewrites';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return Redirect::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'redirect_id';
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
            'icon' => 'corner-up-right',
            'text' => 'Redirects',
            'section' => 'site',
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
        $type = $this->getRequest()->get('action') ?? 'list';
        $redirect = $this->getObject();

        $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
        $websites = $this->getUtils()->getWebsitesSelectOptions();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':

                $redirect_url_from = $redirect_url_to = $redirect_website = $redirect_code = '';
                if ($redirect instanceof Redirect) {
                    $redirect_url_from = $redirect->url_from;
                    $redirect_url_to = $redirect->url_to;
                    $redirect_website = $redirect->website_id;
                    $redirect_code = $redirect->redirect_code;
                }

                $form->addField('url_from', [
                    'type' => 'textfield',
                    'title' => 'Url From',
                    'default_value' => $redirect_url_from,
                    'validate' => ['required'],
                ])->addField('url_to', [
                    'type' => 'textfield',
                    'title' => 'Url To',
                    'default_value' => $redirect_url_to,
                    'validate' => ['required'],
                ])->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $redirect_website,
                    'options' => $websites,
                ])->addField('redirect_code', [
                    'type' => 'select',
                    'title' => 'Redirect Code',
                    'default_value' => $redirect_code,
                    'options' => [
                        '300' => '300 - multiple choices',
                        '301' => '301 - moved permanently',
                        '302' => '302 - found',
                        '303' => '303 - see other',
                        '307' => '307 - temporary redirect',
                        '308' => '308 - permanent redirect',
                    ],
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
         * @var Redirect $redirect
         */
        $redirect = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $redirect->setUrlFrom($values['url_from']);
                $redirect->setUrlTo($values['url_to']);
                $redirect->setWebsiteId(empty($values['website_id']) ? null : $values['website_id']);
                $redirect->setRedirectCode($values['redirect_code']);

                $this->setAdminActionLogData($redirect->getChangedData());

                $redirect->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Redirect Saved."));
                break;
            case 'delete':
                $redirect->delete();

                $this->setAdminActionLogData('Deleted rewrite ' . $redirect->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Redirect Deleted."));

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
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'URL From' => ['order' => 'url_from', 'search' => 'url_from'],
            'URL To' => ['order' => 'url_to', 'search' => 'url_to'],
            'Redirect code' => ['order' => 'redirect_code', 'search' => 'redirect_code'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($redirect) {
                return [
                    'ID' => $redirect->id,
                    'Website' => $redirect->getWebsiteId() == null ? 'All websites' : $redirect->getWebsite()->domain,
                    'URL From' => $redirect->url_from,
                    'URL To' => $redirect->url_to,
                    'Redirect code' => $redirect->redirect_code,
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($redirect->id),
                        static::DELETE_BTN => $this->getDeleteButton($redirect->id),
                    ],
                ];
            },
            $data
        );
    }
}
