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
use App\Site\Controllers\Frontend\Search as SearchController;
use Degami\Basics\Exceptions\BasicException;
use Degami\Basics\Html\TagElement;
use Exception;

/**
 * Search Block
 */
class Search extends BaseCodeBlock
{
    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @return string
     * @throws BasicException
     */
    public function renderHTML(BasePage $current_page = null): string
    {
        if ($current_page instanceof SearchController) {
            return '';
        }

        if (!$this->getEnv('ELASTICSEARCH')) {
            return '';
        }

        try {
            $form_content = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'div',
                'attributes' => [
                    'class' => 'searchbar input-group',
                ],
            ]]);

            $input = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'input',
                'type' => 'text',
                'name' => 'q',
                'attributes' => [
                    'class' => 'form-control',
                ],
            ]]);

            $form_content->addChild($input);

            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'button',
                'type' => 'submit',
                'value' => $this->getUtils()->translate('Search', locale: $current_page?->getCurrentLocale()),
                'attributes' => [
                    'class' => 'btn searchbtn',
                ],
                'text' => $this->getHtmlRenderer()->getIcon('search'),
            ]]);

            $div = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'div',
                'attributes' => [
                    'class' => 'input-group-append',
                ],
            ]]);

            $div->addChild($button);

            $form_content->addChild($div);

            $action_url = $this->getWebRouter()->getUrl('frontend.search.withlang', ['lang' => $this->getApp()->getCurrentLocale()]);
            return '<form class="searchform-mini" action="' . $action_url . '" method="GET">' .
                $form_content .
                '</form>';
        } catch (Exception $e) {
        }
        return "";
    }
}
