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
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Traits\AdminTrait;
use Degami\Basics\Html\TagElement;
use App\Base\Models\User;
use App\Base\Models\Cart;
use App\App;
use App\Base\Abstracts\Controllers\BaseHtmlPage;

/**
 * Link to Cart Block
 */
class LinkToCart extends BaseCodeBlock
{
    protected ?Cart $cart = null;

    public function getCart(?BaseHtmlPage $current_page = null) : ?Cart
    {
        if ($this->cart instanceof Cart) {
            return $this->cart;
        }

        $cart = Cart::getCollection()->where([
            'user_id' => $current_page?->getCurrentUser()->getId(),
            'website_id' => $current_page?->getCurrentWebsite()->getId(),
            'is_active' => true,
        ])->getFirst();

        if ($cart instanceof Cart) {
            $this->cart = $cart;
            return $this->cart;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param BasePage|null $current_page
     * @param array $data
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderHTML(?BasePage $current_page = null, array $data = []): string
    {
        if (!App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) {
            return '';
        }

        $config = array_filter(json_decode($data['config'] ?? '{}', true));
        if (empty($config)) {
            $config = [
                'show-cart-items-number' => true,
            ];
        }

        $route_info = $current_page?->getRouteInfo();

        // $current_page_handler = $route_info->getHandler();
        if ($current_page?->getRouteGroup() == AdminPage::getRouteGroup() || $route_info?->isAdminRoute() || !$current_page?->getCurrentUser()) {
            return '';
        }

        $numItems = count($this->getCart($current_page)?->getItems() ?? []);

        $targetHref = $this->getWebRouter()->getUrl("frontend.commerce.cart");
        $countbadge = '<span class="badge badge-secondary position-absolute" style="bottom: -5px; left: -5px;">'.$numItems.'</span>';


        if (!$config['show-cart-items-number']) {
            $countbadge = '';
        }

        $info = [
            'tag' => 'a',
            'attributes' => [
                'class' => 'cart-link',
                'href' => $targetHref, 
            ],
            'text' => '<div class="m-2 position-relative">' . $this->getIcons()->get('shopping-cart', echo: false) . ' ' .$countbadge.'</div>',
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

        $config_fields[] = $form->getFieldObj('show-cart-items-number', [
            'type' => 'switchbox',
            'title' => 'Show cart items number on link to cart',
            'default_value' => $default_values['show-cart-items-number'] ?? false,
        ]);

        return $config_fields;
    }

    public function isCachable() : bool
    {
        return false;
    }

}
