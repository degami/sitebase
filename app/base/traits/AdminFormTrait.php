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

namespace App\Base\Traits;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * Administration Forms Trait
 */
trait AdminFormTrait
{
    /**
     * adds frontend elements to form
     *
     * @param FAPI\Form $form
     * @param $form_state
     * @param string[] $form_elements
     * @return FAPI\Form
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function addFrontendFormElements(FAPI\Form $form, &$form_state, $form_elements = ['url', 'website_id', 'locale']): FAPI\Form
    {
        /** @var BaseModel $object */
        $object = $this->getObject();

        $form_elements = array_intersect($form_elements, ['url', 'website_id', 'locale']);

        $object_url = $object_website = $object_locale = '';
        if ($object instanceof FrontendModel && $object->isLoaded()) {
            $languages = $this->getUtils()->getSiteLanguagesSelectOptions($object->getWebsiteId());

            foreach ($form_elements as $key => $element) {
                ${"object_" . $element} = $object->{$element};
            }
        } else {
            $languages = $this->getUtils()->getSiteLanguagesSelectOptions($form_state['input_values']['website_id'] ?? null);
        }

        $websites = $this->getUtils()->getWebsitesSelectOptions();

        $fieldset = $form->addField(
            'frontend',
            [
                'type' => 'fieldset',
                'title' => 'Frontend',
                'collapsible' => true,
                'collapsed' => false,
            ]
        );

        if (in_array('url', $form_elements)) {
            $fieldset->addField(
                'url',
                [
                    'type' => 'textfield',
                    'title' => 'Url key',
                    'default_value' => $object_url,
                    'validate' => ['required'],
                ]
            );
        }

        if (in_array('website_id', $form_elements)) {
            $fieldset->addField(
                'website_id',
                [
                    'type' => 'select',
                    'title' => 'Website',
                    'default_value' => $object_website,
                    'options' => $websites,
                    'validate' => ['required'],

                    'ajax_url' => $this->getUrl('crud.app.site.controllers.admin.json.websitelanguagescallback') . '?' . $_SERVER['QUERY_STRING'] . '&object_class=' . urlencode($this->getObjectClass()),
                    'event' => [
                        [
                            'event' => 'change',
                            'callback' => [static::class, 'changedWebsiteCallback'],
                            'target' => 'website-locales-container',
                            'effect' => 'fade',
                            'method' => 'replace',
                        ],
                    ],

                ]
            );
        }

        if (in_array('locale', $form_elements)) {
            if ($form->isPartial() && isset($form_state['build_info']['args'][0]['name']) && $form_state['build_info']['args'][0]['name'] == 'website_id') {
                $chosen_website = $form_state['build_info']['args'][0]['value'];
                $languages = $this->getUtils()->getSiteLanguagesSelectOptions($chosen_website);
            }

            $fieldset->addField(
                'locale',
                [
                    'prefix' => '<div id="website-locales-container">',
                    'suffix' => '</div>',
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $object_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ]
            );
        }

        return $form;
    }

    /**
     * callback for "changed website form select" event
     *
     * @param FAPI\Form $form
     * @return mixed
     */
    public function changedWebsiteCallback(FAPI\Form $form): mixed
    {
        return $form->getField('frontend')->getField('locale');
    }

    /**
     * adds SEO elements to form
     *
     * @param FAPI\Form $form
     * @param $form_state
     * @return FAPI\Form
     * @throws FAPI\Exceptions\FormException
     */
    protected function addSeoFormElements(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $object = $this->getObject();

        $object_meta_description = $object_meta_keywords = $object_html_title = '';
        if ($object instanceof FrontendModel && $object->isLoaded()) {
            $object_meta_description = $object->meta_description;
            $object_meta_keywords = $object->meta_keywords;
            $object_html_title = $object->html_title;
        }

        $fieldset = $form->addField(
            'seo',
            [
                'type' => 'fieldset',
                'title' => 'SEO',
                'collapsible' => true,
                'collapsed' => true,
            ]
        );

        $fieldset->addField(
            'meta_description',
            [
                'type' => 'textfield',
                'title' => 'Meta Description',
                'default_value' => $object_meta_description,
            ]
        )
            ->addField(
                'meta_keywords',
                [
                    'type' => 'textfield',
                    'title' => 'Meta Keywords',
                    'default_value' => $object_meta_keywords,
                ]
            )
            ->addField(
                'html_title',
                [
                    'type' => 'textfield',
                    'title' => 'Html Title',
                    'default_value' => $object_html_title,
                ]
            );


        return $form;
    }
}
