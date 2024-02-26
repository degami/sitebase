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

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Redirect;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Redirects" Admin Page
 */
class Redirects extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_rewrites';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return Redirect::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'redirect_id';
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
            'route_name' => 'admin.redirects',
            'icon' => 'corner-up-right',
            'text' => 'Redirects',
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
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
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
                $this->addBackButton();

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
     * {@inheritdocs}
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
                break;
            case 'delete':
                $redirect->delete();

                $this->setAdminActionLogData('Deleted rewrite ' . $redirect->getId());

                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
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
     * {@inheritdocs}
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
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($redirect->id),
                            $this->getDeleteButton($redirect->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
