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
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Language;

/**
 * "Languages" Admin Page
 */
class Languages extends AdminManageModelsPage
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
        return 'administer_languages';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return Language::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'language_id';
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
            'icon' => 'flag',
            'text' => 'Languages',
            'section' => 'system',
            'order' => 2,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $language = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $language_locale = $language_639_1 = $language_639_2 = $language_name = $language_native = $language_family = '';
                if ($language->isLoaded()) {
                    $language_locale = $language->locale;
                    $language_639_1 = $language->{'639-1'};
                    $language_639_2 = $language->{'639-2'};
                    $language_name = $language->name;
                    $language_native = $language->native;
                    $language_family = $language->family;
                }

                $form->addField('locale', [
                    'type' => 'textfield',
                    'title' => 'Locale',
                    'default_value' => $language_locale,
                    'validate' => ['required'],
                ])->addField('639-1', [
                    'type' => 'textfield',
                    'title' => '639-1',
                    'default_value' => $language_639_1,
                    'validate' => ['required'],
                ])->addField('639-2', [
                    'type' => 'textfield',
                    'title' => '639-2',
                    'default_value' => $language_639_2,
                    'validate' => ['required'],
                ])->addField('name', [
                    'type' => 'textfield',
                    'title' => 'Name',
                    'default_value' => $language_name,
                    'validate' => ['required'],
                ])
                ->addField('native', [
                    'type' => 'textfield',
                    'title' => 'Native',
                    'default_value' => $language_native,
                    'validate' => ['required'],
                ])
                ->addField('family', [
                    'type' => 'textfield',
                    'title' => 'Family',
                    'default_value' => $language_family,
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
         * @var Language $language
         */
        $language = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $language->setLocale($values['locale']);
                $language->{'639-1'} = $values['639-1'];
                $language->{'639-2'} = $values['639-2'];
                $language->setName($values['name']);
                $language->setNative($values['native']);
                $language->setFamily($values['family']);

                $this->setAdminActionLogData($language->getChangedData());

                $this->addSuccessFlashMessage($this->getUtils()->translate("Language Saved."));
                $language->persist();
                break;
            case 'delete':
                $language->delete();

                $this->setAdminActionLogData('Deleted language ' . $language->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Language Deleted."));

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
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Flag' => null,
            '639-1' => ['order' => '639-1', 'search' => '639-1'],
            '639-2' => ['order' => '639-2', 'search' => '639-2'],
            'Name' => ['order' => 'name', 'search' => 'name'],
            'Native' => ['order' => 'native', 'search' => 'native'],
            'Family' => ['order' => 'family', 'search' => 'family'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($language) {
                return [
                    'ID' => $language->id,
                    'Locale' => $language->locale,
                    'Flag' => $this->getHtmlRenderer()->renderFlag($language->locale),
                    '639-1' => $language->{"639-1"},
                    '639-2' => $language->{"639-2"},
                    'Name' => $language->name,
                    'Native' => $language->native,
                    'Family' => $language->family,
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($language->id),
                            $this->getDeleteButton($language->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
