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

namespace App\Base\Blocks;

use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use Degami\Basics\Html\TagElement;
use Exception;
use App\App;

/**
 * Dark Mode Switcher Block
 */
class DarkModeSwitcher extends BaseCodeBlock
{
    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     */
    public function renderHTML(?BasePage $current_page = null, array $data = []): string
    {
        try {
            $checkbox = $this->getApp()->containerMake(TagElement::class, ['options' => [
                'tag' => 'input',
                'id' => 'darkmode-selector',
                'type' => 'checkbox',
                'attributes' => [
                    'class' => 'paginator-items-choice',
                    'style' => 'width: 50px',
                    'checked' => '',
                ],
            ]]);
            $span = $this->getApp()->containerMake(TagElement::class, ['options' => [
                'tag' => 'span',
                'attributes' => [
                    'class' => 'slider',
                ],
            ]]);

            $label = $this->getApp()->containerMake(TagElement::class, ['options' => [
                'tag' => 'label',
                'attributes' => [
                    'class' => 'switch',
                ],
                'children' => [
                    $checkbox,
                    $span,
                ],
            ]]);

            $script = $this->getApp()->containerMake(TagElement::class, ['options' => [
                'tag' => 'script',
                'type' => 'text/javascript',
                'attributes' => [
                    'class' => null,
                ],
                'text' => "
                    (function(\$){
                        cookieStore.get('darkmode').then(function(data){
                            \$('#darkmode-selector').prop('checked', data?.value);
                        });
                        \$('#darkmode-selector').change(function(evt) {
                            if (\$(this).prop('checked')) {
                                \$.cookie('darkmode', \$(this).prop('checked') ? true : null, { expires: 365, path: '/' });
                                \$('body').addClass('dark-mode');
                            } else {
                                \$.removeCookie('darkmode', {path: '/'});
                                \$('body').removeClass('dark-mode');
                            }
                        });
                    })(jQuery)                
                ",
            ]]);

            return "<div class=\"darkmode-switch\">" . __('Dark Mode') . ' ' . $label."</div>"; // . $script;
        } catch (Exception $e) {}
        return "";
    }
}
