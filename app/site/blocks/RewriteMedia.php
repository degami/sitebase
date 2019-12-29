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

use \App\Base\Abstracts\BaseCodeBlock;
use \App\Base\Abstracts\BasePage;
use \App\Site\Models\MediaElementRewrite;
use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi\Accessories\TagElement;
use \Degami\PHPFormsApi as FAPI;

/**
 * Rewrite Media Block
 */
class RewriteMedia extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     */
    public function renderHTML(BasePage $current_page = null, $data = [])
    {
        $images = [];
        $images = array_map(function ($el) {
            $media_rewrite = $this->getContainer()->make(MediaElementRewrite::class, ['dbrow' => $el]);
            return $media_rewrite->getMediaElement()->getImage();
        }, $current_page->getRewrite()->media_element_rewriteList()->fetchAll());


        $tag_attributes = ['class' => 'block-rewritemedia cycle-slideshow'];
        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        if (!empty($config)) {
            foreach ($config as $k => $v) {
                $tag_attributes['data-cycle-'.$k] = $v;
            }
        }

        return (string)(new TagElement([
            'tag' => 'div',
            'attributes' => $tag_attributes,
            'children' => $images,
        ]));
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

        $config_fields[] = $form->getFieldObj('fx', [
            'type' => 'select',
            'title' => 'data-cycle-fx',
            'options' => [
                '' => '',
                'fade' => 'fade',
                'fadeout' => 'fadeout',
                'scrollHorz' => 'scrollHorz',
                'none' => 'none'
            ],
            'default_value' => $default_values['fx'] ?? '',
        ]);

        $config_fields[] = $form->getFieldObj('speed', [
            'type' => 'textfield',
            'title' => 'data-cycle-speed',
            'default_value' => $default_values['speed'] ?? '',
        ]);


        $config_fields[] = $form->getFieldObj('timeout', [
            'type' => 'textfield',
            'title' => 'data-cycle-timeout',
            'default_value' => $default_values['timeout'] ?? '',
        ]);

        return $config_fields;
    }
}
