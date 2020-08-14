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
use \App\Site\Models\Rewrite;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Rewrites" Admin Page
 */
class Rewrites extends AdminManageModelsPage
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
        return 'administer_rewrites';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return Rewrite::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'rewrite_id';
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
        $rewrite = $this->getObject();

        $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
        $websites = ['' => 'All Websites'] + $this->getUtils()->getWebsitesSelectOptions();

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $rewrite_url = $rewrite_route = $rewrite_website = $rewrite_locale = '';
                if ($rewrite instanceof Rewrite) {
                    $rewrite_url = $rewrite->url;
                    $rewrite_route = $rewrite->route;
                    $rewrite_website = $rewrite->website_id;
                    $rewrite_locale = $rewrite->locale;
                }

                $form->addField(
                    'url',
                    [
                    'type' => 'textfield',
                    'title' => 'Url',
                    'default_value' => $rewrite_url,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'route',
                    [
                    'type' => 'textfield',
                    'title' => 'Route',
                    'default_value' => $rewrite_route,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'website_id',
                    [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $rewrite_website,
                    'options' => $websites,
                    ]
                )
                ->addField(
                    'locale',
                    [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $rewrite_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                    ]
                );

                $this->addSubmitButton($form);
                break;

            case 'translations':
                $this->addBackButton();

                $form->addMarkup(
                    '<h4>'.sprintf($this->getUtils()->translate('Translations for <strong>%s</strong>'), $rewrite->getUrl()).'</h4>'
                );

                $other_rewrites = [];
                foreach ($this->getDb()->table('rewrite')->where('id != ?', $rewrite->getId())->fetchAll() as $item) {
                    $other_rewrites[$item->id] = $item->route.' - '.$item->locale . " (".$item->url.")";
                }
                $translations = $rewrite->getTranslations();
                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                unset($languages[$rewrite->getLocale()]);

                if (count($languages) == 0) {
                    $form->addMarkup('<h3 class="text-center">No translation needed!</h3>');
                } else {
                    foreach ($languages as $locale => $language_name) {
                        $form->addField(
                            'translation_'.$locale,
                            [
                            'type' => 'select',
                            'title' => $language_name,
                            'options' => ['' => ''] + $other_rewrites,
                            'default_value' => isset($translations[$locale]) ? $translations[$locale]->id : '',
                            ]
                        );
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
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
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
         * @var Rewrite $rewrite
         */
        $rewrite = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $rewrite->url = $values['url'];
                $rewrite->route = $values['route'];
                $rewrite->website_id = empty($values['website_id']) ? null : $values['website_id'];
                $rewrite->locale = $values['locale'];

                $this->setAdminActionLogData($rewrite->getChangedData());

                $rewrite->persist();
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

                $this->setAdminActionLogData('Deleted rewrite '.$rewrite->getId());

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
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'URL' => ['order' => 'url', 'search' => 'url'],
            'Route' => ['order' => 'route', 'search' => 'route'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
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
