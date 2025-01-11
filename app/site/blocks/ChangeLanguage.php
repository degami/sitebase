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

namespace App\Site\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Controllers\FrontendPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Language;
use Exception;
use Degami\Basics\Html\TagElement;

/**
 * Change Language Block
 */
class ChangeLanguage extends BaseCodeBlock
{
    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     */
    public function renderHTML(BasePage $current_page = null, $data = []): string
    {
        try {
            $config = array_filter(json_decode($data['config'] ?? '{}', true));
            if (empty($config)) {
                $config = [
                    'show-flags' => false,
                    'show-language' => 'code',
                ];
            }


            if (($current_page instanceof FrontendPage) && is_callable([$current_page, 'getTranslations'])) {
                if (($translations = $current_page->getTranslations()) && !empty($translations)) {
                    $changelanguage_links = $this->containerMake(TagElement::class, ['options' => [
                        'tag' => 'ul',
                        'attributes' => [
                            'class' => 'choose-lang',
                        ],
                    ]]);

                    $atags = array_map(
                        function ($el, $key) use ($config) {
                            $text = '';

                            if (isset($config['show-language']) && $config['show-language'] == 'code') {
                                $text = $key;
                            }
                            if (isset($config['show-language']) && $config['show-language'] == 'full') {
                                $language = $this->containerCall([Language::class, 'loadBy'], ['field' => 'locale', 'value' => $key]);
                                $text = $language->native;
                            }
                            if (isset($config['show-flags']) && boolval($config['show-flags'])) {
                                $text .= ' ' . $this->getHtmlRenderer()->renderFlag($key);
                            }

                            $link_options = [
                                'tag' => 'a',
                                'attributes' => [
                                    'class' => '',
                                    'href' => $el,
                                    'title' => strip_tags($text),
                                ],
                                'text' => $text,
                            ];

                            return $this->containerMake(TagElement::class, ['options' => $link_options]);
                        },
                        $translations,
                        array_keys($translations)
                    );
                    foreach ($atags as $atag) {
                        $li = $this->containerMake(
                            TagElement::class,
                            ['options' => [
                                'tag' => 'li',
                                'attributes' => ['class' => ''],
                            ]]
                        );

                        $li->addChild($atag);
                        $changelanguage_links->addChild($li);
                    }

                    return $changelanguage_links;
                }
            }
        } catch (Exception $e) {
        }
        return "";
    }

    /**
     * additional configuration fieldset
     *
     * @param FAPI\Form $form
     * @param $form_state
     * @param $default_values
     * @return array
     * @throws FAPI\Exceptions\FormException
     */
    public function additionalConfigFieldset(FAPI\Form $form, &$form_state, $default_values): array
    {
        $config_fields = [];

        $config_fields[] = $form->getFieldObj('show-language', [
            'type' => 'select',
            'title' => 'Show Language',
            'options' => [
                'full' => 'Full Language Name',
                'code' => 'Language Code',
                'none' => 'None'
            ],
            'default_value' => $default_values['show-language'] ?? '',
        ]);

        $config_fields[] = $form->getFieldObj('show-flags', [
            'type' => 'switchbox',
            'title' => 'Show Flag',
            'default_value' => boolval($default_values['show-flags'] ?? '') ? 1 : 0,
            'yes_value' => 1,
            'yes_label' => 'Yes',
            'no_value' => 0,
            'no_label' => 'No',
            'field_class' => 'switchbox',
        ]);

        return $config_fields;
    }
}
