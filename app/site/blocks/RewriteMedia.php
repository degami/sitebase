<?php

/**
 * SiteBase
 * PHP Version 8.0
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
use App\Base\Exceptions\PermissionDeniedException;
use App\Site\Models\MediaElementRewrite;
use Degami\Basics\Exceptions\BasicException;
use Degami\Basics\Html\TagElement;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Rewrite Media Block
 */
class RewriteMedia extends BaseCodeBlock
{
    /**
     * {@inheritdocs}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    public function renderHTML(BasePage $current_page = null, $data = []): string
    {

        $rewrite_id = [];
        if ($current_page?->getRewrite()?->getId()) {
            $rewrite_id[] = $current_page?->getRewrite()?->getId();
            $rewrite_id[] = null;
        } else {
            $rewrite_id = null;
        }

        $images = array_map(
            function ($media_rewrite) {
                /** @var MediaElementRewrite $media_rewrite */
                return $media_rewrite->getMediaElement()->getImage();
            },
            MediaElementRewrite::getCollection()->where(['rewrite_id' => $rewrite_id])->getItems()
        );


        $tag_attributes = ['class' => 'block-rewritemedia cycle-slideshow'];
        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        if (!empty($config)) {
            foreach ($config as $k => $v) {
                $tag_attributes['data-cycle-' . $k] = $v;
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
