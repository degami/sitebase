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
use \App\Site\Models\Rewrite;

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
        return 'rewrites';
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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $rewrite = null;
        if ($this->getRequest()->get('rewrite_id')) {
            $rewrite = $this->loadObject($this->getRequest()->get('rewrite_id'));
        }

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
                    )
                    ->addField(
                        'button',
                        [
                        'type' => 'submit',
                        'value' => 'ok',
                        'container_class' => 'form-item mt-3',
                        'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                        ]
                    );
                break;

            case 'translations':
                $this->addBackButton();

                $other_rewrites = [];
                foreach ($this->getDb()->table('rewrite')->where('id != ?', $rewrite->getId())->fetchAll() as $item) {
                    $other_rewrites[$item->id] = $item->route.' - '.$item->locale;
                }
                $translations = $rewrite->getTranslations();
                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                unset($languages[$rewrite->getLocale()]);
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
                $form->addField(
                    'button',
                    [
                    'type' => 'submit',
                    'container_tag' => null,
                    'prefix' => '&nbsp;',
                    'value' => 'Ok',
                    'attributes' => ['class' => 'btn btn-primary btn-block'],
                    ]
                );
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
 * @var Rewrite $rewrite
*/
        $rewrite = $this->newEmptyObject();
        if ($this->getRequest()->get('rewrite_id')) {
            $rewrite = $this->loadObject($this->getRequest()->get('rewrite_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $rewrite->url = $values['url'];
                $rewrite->route = $values['route'];
                $rewrite->website_id = empty($values['website_id']) ? null : $values['website_id'];
                $rewrite->locale = $values['locale'];
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
            'Website' => 'website_id',
            'Url' => 'url',
            'Route' => 'route',
            'Locale' => null,
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
            function ($rewrite) {
                return [
                'ID' => $rewrite->id,
                'Website' => $rewrite->getWebsiteId() == null ? 'All websites' : $rewrite->getWebsite()->domain,
                'Url' => $rewrite->url,
                'Route' => $rewrite->route,
                'Locale' => $rewrite->getLocale(),
                'actions' => '<a class="btn btn-success btn-sm" href="'. $this->getControllerUrl() .'?action=translations&rewrite_id='. $rewrite->id.'">'.$this->getUtils()->getIcon('tag') .'</a>
                    <a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&rewrite_id='. $rewrite->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&rewrite_id='. $rewrite->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
