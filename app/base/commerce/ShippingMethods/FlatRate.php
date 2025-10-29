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

namespace App\Base\Commerce\ShippingMethods;

use App\App;
use App\Base\Interfaces\Commerce\ShippingMethodInterface;
use Degami\PHPFormsApi as FAPI;
use Degami\PHPFormsApi\Containers\SeamlessContainer;
use App\Base\Models\Cart;
use App\Base\Models\CartItem;
use Degami\PHPFormsApi\Accessories\FormValues;

class FlatRate implements ShippingMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCode() : string
    {
        return 'flatrate';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Flat Rate';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(Cart $cart): bool
    {
        return App::getInstance()->getSiteData()->getConfigValue('shipping/flatrate/active') == true;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Cart $cart): bool
    {
        return $cart->requireShipping();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $form
            ->addField('cost', [
                'type' => 'number',
                'title' => 'Shipping Cost',
                'min' => 0,
                'max' => PHP_INT_MAX,
                'step' => 0.1,
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('shipping/flatrate/cost'),
            ])
            ->addField('apply_to', [
                'type' => 'select',
                'title' => 'Apply cost to',
                'options' => [
                    'cart' => 'Once',
                    'cart_items' => 'Once per cart item',
                    'products' => 'Each product (with qty)',
                ],
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('shipping/flatrate/apply_to'),
            ]);
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state) : FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('flatrate', [
            'type' => 'seamless_container',
        ]);

        $totalCosts = $this->calculateShipping(null, $cart)['shipping_cost'];
        $totalCostsFormatted = App::getInstance()->getUtils()->formatPrice($totalCosts, $cart->getCurrencyCode());

        $out->addField('shipping_costs', [
            'type' => 'markup',
            'value' => App::getInstance()->getUtils()->translate("Total Shipping costs: %s", [$totalCostsFormatted]),
        ]);

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateShipping(?FormValues $values, Cart $cart) : array
    {
        $cost = App::getInstance()->getSiteData()->getConfigValue('shipping/flatrate/cost');
        $applyTo = App::getInstance()->getSiteData()->getConfigValue('shipping/flatrate/apply_to');

        $totalCosts = match($applyTo) {
            'cart_items' => $cost * count(array_filter($cart->getItems(), fn(CartItem $item) => $item->requireShipping())),
            'products' => $cost * array_sum(array_map(fn(CartItem $item) => $item->requireShipping() ? $item->getQuantity() : 0, $cart->getItems())),
            'cart' => $cost,
            default => $cost,
        };

        return [
            'shipping_cost' => $totalCosts, 
            'additional_data' => ['calculate_when' => time()]
        ];
    }
}