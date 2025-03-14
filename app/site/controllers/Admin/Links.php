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

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\LinkExchange;
use App\Site\Models\Taxonomy;

/**
 * "Links" Admin Page
 */
class Links extends AdminManageFrontendModelsPage
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
        return 'administer_links';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return LinkExchange::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'link_id';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'link',
            'text' => 'Links',
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
        $link = $this->getObject();

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
                    $this->getUrl('crud.app.site.controllers.admin.json.linkterms', ['id' => $this->getRequest()->get('link_id')]) . '?link_id=' . $this->getRequest()->get('link_id') . '&action=new',
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
                $form->addField('url', [
                    'type' => 'textfield',
                    'title' => 'Link url',
                    'default_value' => $link_url,
                    'validate' => ['required'],
                ])->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $link_title,
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
                    'tinymce_options' => DEFAULT_TINYMCE_OPTIONS,
                    'default_value' => $link_description,
                    'rows' => 20,
                ])->addField('active', [
                    'type' => 'switchbox',
                    'title' => 'Active',
                    'default_value' => boolval($link_active) ? 1 : 0,
                    'yes_value' => 1,
                    'yes_label' => 'Yes',
                    'no_value' => 0,
                    'no_label' => 'No',
                    'field_class' => 'switchbox',
                ]);

                $this->addFrontendFormElements($form, $form_state, ['website_id', 'locale']);
                $this->addSubmitButton($form);

                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;

            case 'term_deassoc':
                $term = $this->containerCall([Taxonomy::class, 'load'], ['id' => $this->getRequest()->get('term_id')]);
                $form->addField('link_id', [
                    'type' => 'hidden',
                    'default_value' => $link->id,
                ])->addField('taxonomy_id', [
                    'type' => 'hidden',
                    'default_value' => $term->id,
                ])->addField('confirm', [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the "' . $link->title . '"  from the "' . $term->title . '" term (ID: ' . $term->id . ') ?',
                    'suffix' => '<br /><br />',
                ])
                ->addMarkup('<a class="btn btn-danger btn-sm" href="' . $this->getUrl('crud.app.site.controllers.admin.json.termlinks', ['id' => $term->id]) . '?term_id=' . $term->id . '&action=page_assoc">Cancel</a>');

                $this->addSubmitButton($form, true);

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
        $values = $form->values();
        // @todo : check if page language is in page website languages?
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
         * @var LinkExchange $link
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

                $this->addSuccessFlashMessage($this->getUtils()->translate("Link Saved."));
                break;
            case 'delete':
                $link->delete();

                $this->setAdminActionLogData('Deleted link ' . $link->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Link Deleted."));

                break;
            case 'term_deassoc':
                if ($values['term_id']) {
                    $term = $this->containerCall([Taxonomy::class, 'load'], ['id' => $values['term_id']]);
                    $link->removeTerm($term);
                }
                break;
        }
        if ($this->getRequest()->request->get('term_id') != null) {
            return new JsonResponse(['success' => true]);
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
            'URL' => ['order' => 'url', 'search' => 'url'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Active' => 'active',
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($link) {
                return [
                    'ID' => $link->id,
                    'Website' => $link->getWebsiteId() == null ? 'All websites' : $link->getWebsite()->domain,
                    'URL' => $link->url,
                    'Locale' => $link->locale,
                    'Title' => $link->title,
                    'Active' => $this->getUtils()->translate(boolval($link->active) ? 'Yes' : 'No', locale: $this->getCurrentLocale()),
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
