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
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \App\Base\Abstracts\Controllers\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Configuration;

/**
 * "Config" Admin Page
 */
class Config extends AdminManageModelsPage
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
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return Configuration::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'config_id';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     */
    protected function getTemplateData(): array
    {
        if ($this->template_data['action'] == 'list') {
            $data = $this->getContainer()->call([Configuration::class, 'paginate']);
            $this->template_data += [
                'configs' => $data['items'],
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
            ];
        }
        return $this->template_data;
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
        $configuration = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        if ($type == 'new') {
            $configuration->setWebsiteId($this->getSiteData()->getCurrentWebsite()->getId());
        }

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $languages = [null => $this->getUtils()->translate('All languages')] + $this->getUtils()->getSiteLanguagesSelectOptions($configuration->getWebsiteId());
                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $configuration_path = $configuration_value = $configuration_website = $configuration_locale = '';
                if ($configuration->isLoaded()) {
                    $configuration_path = $configuration->path;
                    $configuration_value = $configuration->value;
                    $configuration_website = $configuration->website_id;
                    $configuration_locale = $configuration->locale;
                }
                $form->addField('path', [
                    'type' => 'textfield',
                    'title' => 'Configuration Path',
                    'default_value' => $configuration_path,
                    'validate' => ['required'],
                ])
                ->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $configuration_website,
                    'options' => $websites,
                    'validate' => ['required'],
                ])
                ->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $configuration_locale,
                    'options' => $languages,
                    // 'validate' => ['required'],
                ])
                ->addField('value', [
                    'type' => 'textarea',
                    'title' => 'Configuration Value',
                    'default_value' => $configuration_value,
                    'rows' => 3,
                    // 'validate' => ['required'],
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
        // $values = $form->values()
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws PhpfastcacheSimpleCacheException|BasicException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        /**
         * @var Configuration $configuration
         */
        $configuration = $this->getObject();

        if ($this->getRequest()->get('config_id')) {
            if (($values['action'] == 'edit' || $values['action'] == 'delete') && $this->getCache()->has('site.configuration')) {
                $cached_config = $this->getSiteData()->getCachedConfig();
                if (isset($cached_config[$configuration->path])) {
                    unset($cached_config[$configuration->path]);
                    $this->getCache()->set('site.configuration', $cached_config);
                }
            }
        }

        switch ($values['action']) {
            case 'new':
            case 'edit':
                $configuration->path = $values['path'];
                $configuration->value = $values['value'];
                $configuration->website_id = $values['website_id'];
                $configuration->locale = !empty($values['locale']) ? $values['locale'] : null;

                $this->setAdminActionLogData($configuration->getChangedData());

                $configuration->persist();

                break;
            case 'delete':
                $configuration->delete();

                $this->setAdminActionLogData('Deleted config ' . $configuration->getId());

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
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Path' => ['order' => 'path', 'search' => 'path'],
            'Value' => null,
            'Is System' => 'is_system',
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     */
    protected function getTableElements($data): array
    {
        return array_map(
            function ($config) {
                return [
                    'ID' => $config->id,
                    'Website' => $config->getWebsiteId() == null ? $this->getUtils()->translate('All websites', $this->getCurrentLocale()) : $config->getWebsite()->domain,
                    'Locale' => $config->getLocale() == null ? $this->getUtils()->translate('All languages', $this->getCurrentLocale()) : $config->getLocale(),
                    'Path' => $config->path,
                    'Value' => $config->value,
                    'Is System' => $config->is_system ? $this->getUtils()->getIcon('check') : '&nbsp;',
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($config->id),
                            (!$config->getIsSystem()) ? $this->getDeleteButton($config->id) : '',
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
