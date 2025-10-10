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
Use Degami\PHPFormsApi\Containers\Fieldset;

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

            if ($form->getField('title')) {
                $form->addJs("
                    \$('#title').on('change', function() {
                        if (\$('#url').val() != '') {
                            return;
                        }
                        var url = \$(this).val();
                        if (url == '') {
                            return;
                        }
                        url = url.replace(/[^a-zA-Z0-9\\-\\_]+/g, '-');
                        \$('#url').val(url);
                    });
                ");
            }
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

                    'ajax_url' => $this->getUrl('crud.app.site.controllers.admin.json.websitelanguagescallback') . '?' . $_SERVER['QUERY_STRING'] . '&object_class=' . urlencode(static::getObjectClass()),
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

        /** @var Fieldset $fieldset */
        $fieldset = $form->addField('seo', [
            'type' => 'fieldset',
            'title' => 'SEO',
            'collapsible' => true,
            'collapsed' => true,
        ]);

        $fieldset
            ->addField('meta_description', [
                'type' => 'textfield',
                'title' => 'Meta Description',
                'default_value' => $object_meta_description,
            ])
            ->addField('meta_keywords', [
                'type' => 'textfield',
                'title' => 'Meta Keywords',
                'default_value' => $object_meta_keywords,
            ])
            ->addField('html_title', [
                'type' => 'textfield',
                'title' => 'Html Title',
                'default_value' => $object_html_title,
            ]);
        
        if ($this->getAI()->isAiAvailable()) {
            $promptText = $this->getUtils()->translate("Generate a json with meta_description, meta_keywords, html_title using language \":language\" for the text: \\n:text");

            $ai_options = [];
            foreach ($this->getAI()->getAvailableAIs(true) as $aiCode => $aiName) {
                if ($this->getAI()->isAiAvailable($aiCode)) {
                    $ai_options[$aiCode] = $aiName['name'];
                }
            }

            $fieldset
                ->addField('ai_chooser_container', [
                    'type' => 'tag_container',
                    'attributes' => [
                        'class' => 'd-flex flex-row gap-2 align-items-center',
                    ],
                    'suffix' => '<small>Generate Meta Description / Meta Keywords / Html Title using AI</small>',
                ])
                ->addField('ai_generator_chooser', [
                    'type' => 'select',
                    'title' => '',
                    'default_value' => $this->getAuth()->getCurrentUser()->getUserSession()->getSessionKey('uiSettings')['preferredAI'] ?? '',
                    'options' => $ai_options,
                ])
                ->addField('ai_meta_generate', [
                    'type' => 'button',
                    'default_value' => 'Generate',
                    'attributes' => [
                        'class' => 'btn btn-primary mr-2',
                    ],
                    'onclick' => 'generateMetaDescription()',
                ])
                ->addJs("\$('#ai_meta_generate').off('click').on('click', function(e) {
                        var that = this;
                        e.preventDefault();
                        var availableAIs = ['".implode("','", $this->getAI()->getAvailableAIs())."'];
                        var selected = \$('#ai_generator_chooser').val();
                        if (selected == '') {
                            alert('".$this->getutils()->translate("Please select an AI generator")."');
                            return;
                        }
                        var elementContent = $('#content').val();
                        if (tinymce.get('content')) {
                            elementContent = tinymce.get('content').getContent();
                        }
                        var locale = $('#locale').val();
                        var promptText = '".$promptText."'.replace(':language', locale).replace(':text', elementContent);

                        var responseCallback = function(response) {
                            if (response.success == true) {
                                var match = response.text.match(/```json([\s\S]*?)```/);
                                if (match && match[1]) {
                                    try {
                                        var json = JSON.parse(match[1].trim());
                                        console.log(json);
                                        $('#meta_description').val(json.meta_description);
                                        $('#meta_keywords').val(json.meta_keywords);
                                        $('#html_title').val(json.html_title);
                                    } catch (e) {
                                        alert('Error parsing JSON: ' + e.message);
                                    }
                                } else {
                                    alert('No JSON block found in response');
                                }
                            } else {
                                alert('Error: ' + response.message);
                            }
                            $(that).prop('disabled',false).next('.loader').remove();
                        };

                        $(that).prop('disabled',true).after('<div class=\"loader d-inline-flex ml-5\" style=\"zoom: 0.5\"></div>');
                        if (availableAIs.includes(selected)) {
                            $('#admin').appAdmin('askAI', selected, {'prompt' : promptText}, responseCallback);
                        } else {
                            alert('".$this->getUtils()->translate("Error: AI generator not found")."');
                            $(that).prop('disabled',false).next('.loader').remove();
                        }
                })");
        }

        return $form;
    }
}
