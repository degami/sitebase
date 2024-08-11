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
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Traits\AdminTrait;
use Degami\Basics\Html\TagElement;
use App\Site\Models\User;

/**
 * AuthorInfo Block
 */
class AuthorInfo extends BaseCodeBlock
{
    public const DATE_FORMAT = 'M j, Y - H:i';

    /**
     * {@inheritdocs}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderHTML(BasePage $current_page = null, $data = []): string
    {
        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        if (empty($config)) {
            $config = [
                'date-format' => self::DATE_FORMAT,
            ];
        }

        $route_info = $current_page->getRouteInfo();

        // $current_page_handler = $route_info->getHandler();
        if ($current_page->getRouteGroup() == AdminTrait::getRouteGroup() || $route_info->isAdminRoute()) {
            return '';
        }

        if (!is_subclass_of($current_page, FrontendPageWithObject::class) || $this->containerCall([$current_page, 'canShowAuthorInfo']) == false) {
            return '';
        }

        /** @var FrontendPageWithObject $current_page */

        /** @var User $author */
        $author = $current_page->getObject()->getOwner();

        $info = [
            'tag' => 'div',
            'attributes' => [
                'class' => 'author-info',
            ],
            'text' => __('by:').' <strong>' . $author->getDisplayName() . '</strong> '.__('on:').' ' . date_format(new \DateTime($current_page->getObject()->getCreatedAt()), $config['date-format']),
        ];

        return "".$this->containerMake(TagElement::class, ['options' => $info]);
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

        $config_fields[] = $form->getFieldObj('date-format', [
            'type' => 'textfield',
            'title' => 'Add current page to breadcrumb',
            'default_value' => $default_values['date-format'] ?? self::DATE_FORMAT,
        ]);

        return $config_fields;
    }
}
