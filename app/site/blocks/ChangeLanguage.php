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
namespace App\Site\Blocks;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\BaseCodeBlock;
use \App\Base\Abstracts\BasePage;
use \Degami\PHPFormsApi as FAPI;
use \Degami\PHPFormsApi\Accessories\TagElement;
use \App\Site\Models\Language;
use \Exception;

/**
 * Change Language Block
 */
class ChangeLanguage extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     */
    public function renderHTML(BasePage $current_page = null, $data = [])
    {
        try {
            $config = array_filter(json_decode($data['config'] ?? '{}', true));
            if (empty($config)) {
                $config = [
                    'show-flags' => false,
                    'show-language' => 'code',
                ];
            }

            if (is_callable([$current_page, 'getTranslations'])) {
                if (($translations = $current_page->getTranslations()) && !empty($translations)) {
                    return '<ul class="choose-lang"><li>'.implode('</li><li>', array_map(function ($el, $key) use ($config) {
                        $text = '';
                        $language = $this->getContainer()->call([Language::class, 'loadBy'], ['field' => 'locale', 'value' => $key]);

                        if ($config['show-language'] == 'code') {
                            $text = $key;
                        }
                        if ($config['show-language'] == 'full') {
                            $text = $language->native;
                        }
                        if (boolval($config['show-flags'])) {
                            $text .= ' '.$this->getHtmlRenderer()->renderFlag($key);
                        }

                        return '<a href="'.$el.'">'.$text.'</a>';
                    }, $translations, array_keys($translations))).'</li></ul>';
                }
            }
        } catch (Exception $e) {
        }
        return "";
    }

    /**
     * additional configuration fieldset
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @param  array    $default_values
     * @return FAPI\Form
     */
    public function additionalConfigFieldset(FAPI\Form $form, &$form_state, $default_values)
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
