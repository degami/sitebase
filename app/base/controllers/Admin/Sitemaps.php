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

use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Abstracts\Base\Element;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Sitemap;
use App\Base\Models\Rewrite;
use App\Base\Controllers\Admin\Json\SitemapCallback;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Sitemaps" Admin Page
 */
class Sitemaps extends AdminManageModelsPage
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
        return 'administer_sitemaps';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return Sitemap::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'sitemap_id';
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
            'text' => 'Sitemaps',
            'section' => 'site',
        ];
    }

    protected function beforeRender() : BasePage|Response
    {
        if ($this->getRequest()->get('action') == 'generate') {
            $this->addSuccessFlashMessage($this->getUtils()->translate('Sitemap Generated.'));
            /** @var Sitemap $sitemap */
            $sitemap = $this->getObject();
            $sitemap->generate();

            return $this->refreshPage();
        }

        return parent::beforeRender();
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
        $sitemap = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
                // intentional fall-trough
            case 'new':
                $this->addBackButton();

                if ($sitemap->isLoaded()) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($sitemap->getAppWebsite()?->getId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $sitemap_title = $sitemap_website = $sitemap_locale = '';
                if ($sitemap->isLoaded()) {
                    $sitemap_title = $sitemap->title;
                    $sitemap_website = $sitemap->website;
                    $sitemap_locale = $sitemap->locale;
                }
                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $sitemap_title,
                    'validate' => ['required'],
                ])->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $sitemap_website,
                    'options' => $websites,
                    'validate' => ['required'],
                ])->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $sitemap_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ]);

                if ($sitemap->isLoaded()) {
                    $fieldset = $form->addField('urlset', [
                        'prefix' => '<div id="fieldset-urlsetfields"><label class="label-tag_container">Urls</label>',
                        'suffix' => '</div>',
                        'type' => 'tag_container',
                        'tag' => 'div',
                    ]);

                    $urlset = $sitemap->getUrlset();
                    foreach ($urlset['url'] as $index => $component) {
                        $component['effaceable'] = true;
                        $this->addComponent(
                            $fieldset->addField(
                                'url_' . $index . '',
                                [
                                    'type' => 'fieldset',
                                    'title' => $component['loc'],
                                    'collapsible' => true,
                                    'collapsed' => true,
                                ],
                                $index
                            ),
                            $index,
                            $sitemap,
                            $component
                        );
                    }
                    if ($form->isPartial() || count($urlset['url']) < ($form_state['input_values']['num_components'] ?? 0)) {
                        $num_fields = $form_state['input_values']['num_components'] + ($form->isPartial() ? 1 : 0);
                        for ($i = 0; $i < ($num_fields - count($urlset['url'])); $i++) {
                            $index = count($urlset['url']) + $i;
                            $this->addComponent(
                                $fieldset->addField(
                                    'url_' . $index . '',
                                    [
                                        'type' => 'fieldset',
                                        'title' => 'New Url - ' . ($index + 1),
                                        'collapsible' => true,
                                        'collapsed' => true,
                                    ],
                                    $index
                                ),
                                $index,
                                $sitemap
                            );
                        }
                        $fieldset->addJs("\$('input[name=\"num_components\"]','#" . $form->getFormId() . "').val('" . count($form->getField('urlset')->getFields()) . "');");
                        $fieldset->addJs("\$('select','#" . $form->getFormId() . "').select2({'width':'100%'});");
                    }

                    $form->addField('num_components', [
                        'type' => 'hidden',
                        'default_value' => count($form->getField('urlset')->getFields()),
                    ]);

                    $form->addField('addmore', [
                        'type' => 'submit',
                        'value' => 'Add more',
                        'ajax_url' => $this->getUrl('crud.app.base.controllers.admin.json.sitemapcallback') . '?action=' . $this->getRequest()->get('action') . '&sitemap_id=' . $this->getRequest()->get('sitemap_id'),
                        'event' => [
                            [
                                'event' => 'click',
                                'callback' => [SitemapCallback::class, 'sitemapFormsCallback'],
                                'target' => 'fieldset-urlsetfields',
                                'effect' => 'fade',
                                'method' => 'replace',
                            ],
                        ],
                    ]);
                }

                $this->addSubmitButton($form);

                if ($sitemap->isLoaded()) {
                    $form->addField('save_publish', [
                        'type' => 'submit',
                        'value' => 'save & publish',
                        'container_class' => 'form-item mt-3',
                        'attributes' => ['class' => 'btn btn-success btn-lg btn-block'],
                    ]);
                }
                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * adds a component
     *
     * @param Element $form_component
     * @param int $index
     * @param Sitemap $sitemap
     * @param array|null $component
     * @return Element
     */
    private function addComponent(Element $form_component, int $index, Sitemap $sitemap, ?array $component = null): Element
    {
        if (is_null($component)) {
            $component = [
                'id' => '',
                'rewrite' => '',
                'priority' => '0.5',
                'changefreq' => 'weekly',
                'effaceable' => false,
            ];
        }

        $rewrite_options = [];
        foreach (Rewrite::getCollection()->where(['locale' => $sitemap->getLocale()]) as $rewrite) {
            $rewrite_options[$rewrite->id] = $rewrite->url;
        }

        $form_component->addField('url_' . $index . '[id]', [
            'type' => 'hidden',
            'default_value' => $component['id'],
        ])->addField('url_' . $index . '[rewrite]', [
            'title' => 'Rewrite',
            'type' => 'select',
            'options' => $rewrite_options,
            'default_value' => $component['rewrite'],
        ])->addField('url_' . $index . '[priority]', [
            'title' => 'Priority',
            'type' => 'textfield',
            'default_value' => floatval($component['priority']),
        ])->addField('url_' . $index . '[changefreq]', [
            'title' => 'Change Frequency',
            'type' => 'select',
            'options' => [
                'always' => 'always',
                'hourly' => 'hourly',
                'daily' => 'daily',
                'weekly' => 'weekly',
                'monthly' => 'monthly',
                'yearly' => 'yearly',
                'never' => 'never',
            ],
            'default_value' => $component['changefreq'],
        ]);

        if (isset($component['effaceable']) && $component['effaceable'] == true) {
            $form_component->addField('url_' . $index . '[delete]', [
                'title' => 'Delete Field',
                'type' => 'checkbox',
                'default_value' => '1',
                'value' => '0',
            ]);
        }

        return $form_component;
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
         * @var Sitemap $sitemap
         */
        $sitemap = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $sitemap->setUserId($this->getCurrentUser()->getId());
            // intentional fall trough
            // no break
            case 'edit':
                $sitemap->setTitle($values['title']);
                $sitemap->setWebsiteId($values['website_id']);
                $sitemap->setLocale($values['locale']);

                $sitemap->persist();

                if ($values->urlset) {
                    foreach ($values->urlset as $key => $row) {
                        $db_row = $this->getDb()->createRow('sitemap_rewrite');
                        if (is_numeric($row[$key . '[id]'])) {
                            $db_row = $this->getDb()->table('sitemap_rewrite', $row[$key . '[id]']);
                        }

                        $data = [];

                        $data['sitemap_id'] = $sitemap->id;
                        $data['rewrite_id'] = $row[$key . '[rewrite]'];
                        $data['priority'] = floatval($row[$key . '[priority]']);
                        $data['change_freq'] = $row[$key . '[changefreq]'];

                        if ($db_row->exists() && $row[$key . '[delete]'] == '1') {
                            $db_row->delete();
                        } else {
                            $db_row->update($data);
                        }
                    }
                }

                if ($values['action'] == 'new') {
                    $this->addSuccessFlashMessage($this->getUtils()->translate('Sitemap Saved. Now you can add urls.'));
                    return $this->doRedirect($this->getControllerUrl() . '?action=edit&sitemap_id=' . $sitemap->id);
                }

                if ($form->getTriggeringElement()->getName() == 'save_publish') {
                    $this->addSuccessFlashMessage($this->getUtils()->translate('Sitemap Generated.'));
                    $sitemap->generate();
                }

                break;
            case 'delete':
                $sitemap->delete();

                $this->addInfoFlashMessage($this->getUtils()->translate("Sitemap Deleted."));

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
            'Site Name' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Is Published' => 'published_on',
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($sitemap) {
                return [
                    'ID' => $sitemap->id,
                    'Site Name' => $sitemap->getWebsite()->site_name,
                    'Locale' => $sitemap->locale,
                    'Title' => $sitemap->title,
                    'Is Published' => $this->getUtils()->translate($sitemap->getPublishedOn() != null && $sitemap->getContent() != null ? 'Yes' : 'No', locale: $this->getCurrentLocale()),
                    'actions' => implode(
                        " ",
                        [
                            ($sitemap->getPublishedOn() != null && $sitemap->getContent() != null ? $this->getFrontendModelButton($sitemap) : ''),
                            $this->getActionButton('generate', $sitemap->getId(), 'btn btn-warning generate', 'rss', 'Generate'),
                            $this->getEditButton($sitemap->id),
                            $this->getDeleteButton($sitemap->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
