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
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Rewrite;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Rewrites" Admin Page
 */
class Rewrites extends AdminManageModelsPage
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
    public function getObjectClass(): string
    {
        return Rewrite::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'rewrite_id';
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
            'icon' => 'globe',
            'text' => 'Rewrites',
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
        /** @var Rewrite $rewrite */
        $rewrite = $this->getObject();

        $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
        $websites = ['' => 'All Websites'] + $this->getUtils()->getWebsitesSelectOptions();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $rewrite_url = $rewrite_route = $rewrite_website = $rewrite_locale = '';
                if ($rewrite instanceof Rewrite) {
                    $rewrite_url = $rewrite->getUrl();
                    $rewrite_route = $rewrite->getRoute();
                    $rewrite_website = $rewrite->getWebsiteId();
                    $rewrite_locale = $rewrite->getLocale();
                }

                $form->addField('url', [
                    'type' => 'textfield',
                    'title' => 'Url',
                    'default_value' => $rewrite_url,
                    'validate' => ['required'],
                ])->addField('route', [
                    'type' => 'textfield',
                    'title' => 'Route',
                    'default_value' => $rewrite_route,
                    'validate' => ['required'],
                ])->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $rewrite_website,
                    'options' => $websites,
                ])->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $rewrite_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ]);

                $this->addSubmitButton($form);
                break;

            case 'translations':
                $this->addBackButton();

                $form->addMarkup(
                    '<h4>' . $this->getUtils()->translate('Translations for <strong>%s</strong>', locale: $this->getCurrentLocale(), params: [$rewrite->getUrl()]) . '</h4>'
                );

                $other_rewrites = [];
                foreach (Rewrite::getCollection()->where(['id:not' => $rewrite->getId()]) as $item) {
                    /** @var Rewrite $item */
                    $other_rewrites[$item->getId()] = $item->getRoute() . ' - ' . $item->getLocale() . " (" . $item->getUrl() . ")";
                }
                $translations = $rewrite->getTranslations();
                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                unset($languages[$rewrite->getLocale()]);

                if (count($languages) == 0) {
                    $form->addMarkup('<h3 class="text-center">No translation needed!</h3>');
                } else {
                    foreach ($languages as $locale => $language_name) {
                        $form->addField('translation_' . $locale, [
                            'type' => 'select',
                            'title' => $language_name,
                            'options' => ['' => ''] + $other_rewrites,
                            'default_value' => isset($translations[$locale]) ? $translations[$locale]->id : '',
                        ]);
                    }

                    $this->addSubmitButton($form, true);
                }
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
         * @var Rewrite $rewrite
         */
        $rewrite = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $rewrite->setUrl($values['url']);
                $rewrite->setRoute($values['route']);
                $rewrite->setWebsiteId(empty($values['website_id']) ? null : $values['website_id']);
                $rewrite->setLocale($values['locale']);

                $this->setAdminActionLogData($rewrite->getChangedData());

                $rewrite->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Rewrite Saved."));
                break;
            case 'translations':
                foreach ($values as $key => $value) {
                    if (preg_match("/^translation_(.*?)$/i", $key, $matches)) {
                        $locale = $matches[1];
                        $rewrite_translation_row = $this->getDb()->table('rewrite_translation')->where(
                            [
                                'source' => $rewrite->getId(),
                                'destination_locale' => $locale,
                            ]
                        )->fetch();
                        if (!$rewrite_translation_row) {
                            $rewrite_translation_row = $this->getDb()->table('rewrite_translation')->createRow();
                        }

                        $rewrite_translation_row->update(
                            [
                                'source' => $rewrite->getId(),
                                'source_locale' => $rewrite->getLocale(),
                                'destination' => !empty($value) ? $value : null,
                                'destination_locale' => $locale
                            ]
                        );
                    }
                }
                break;
            case 'delete':
                $rewrite->delete();

                $this->setAdminActionLogData('Deleted rewrite ' . $rewrite->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Rewrite Deleted."));

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
            'URL' => ['order' => 'url', 'search' => 'url'],
            'Route' => ['order' => 'route', 'search' => 'route'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
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
            function ($rewrite) {
                return [
                    'ID' => $rewrite->id,
                    'Website' => $rewrite->getWebsiteId() == null ? 'All websites' : $rewrite->getWebsite()->domain,
                    'URL' => $rewrite->url,
                    'Route' => $rewrite->route,
                    'Locale' => $rewrite->getLocale(),
                    'actions' => implode(
                        " ",
                        [
                            $this->getActionButton('translations', $rewrite->id, 'success', 'tag', 'Translations'),
                            $this->getEditButton($rewrite->id),
                            $this->getDeleteButton($rewrite->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
