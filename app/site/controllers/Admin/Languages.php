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
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Language;

/**
 * "Languages" Admin Page
 */
class Languages extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'languages';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_languages';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return Language::class;
    }

   /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'language_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $language = $this->getObject();

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

                $language_locale = $language_639_1 = $language_639_2 = $language_name = $language_native = $language_family = '';
                if ($language->isLoaded()) {
                    $language_locale = $language->locale;
                    $language_639_1 = $language->{'639-1'};
                    $language_639_2 = $language->{'639-2'};
                    $language_name = $language->name;
                    $language_native = $language->native;
                    $language_family = $language->family;
                }

                $form->addField(
                    'locale',
                    [
                    'type' => 'textfield',
                    'title' => 'Locale',
                    'default_value' => $language_locale,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    '639-1',
                    [
                    'type' => 'textfield',
                    'title' => '639-1',
                    'default_value' => $language_639_1,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    '639-2',
                    [
                    'type' => 'textfield',
                    'title' => '639-2',
                    'default_value' => $language_639_2,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'name',
                    [
                    'type' => 'textfield',
                    'title' => 'Name',
                    'default_value' => $language_name,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'native',
                    [
                    'type' => 'textfield',
                    'title' => 'Native',
                    'default_value' => $language_native,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'family',
                    [
                    'type' => 'textfield',
                    'title' => 'Family',
                    'default_value' => $language_family,
                    ]
                );

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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /**
         * @var Language $language
         */
        $language = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $language->locale = $values['locale'];
                $language->{'639-1'} = $values['639-1'];
                $language->{'639-2'} = $values['639-2'];
                $language->name = $values['name'];
                $language->native = $values['native'];
                $language->family = $values['family'];

                $language->persist();
                break;
            case 'delete':
                $language->delete();
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
     * {@inheritdocs}
     *
     * @param  array $data
     * @return array
     */
    protected function getTableElements($data)
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
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&language_id='. $language->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&language_id='. $language->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
