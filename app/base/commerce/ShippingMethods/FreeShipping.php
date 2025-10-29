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
use Degami\PHPFormsApi as FAPI;
use Degami\PHPFormsApi\Containers\SeamlessContainer;
use App\Base\Models\Cart;
use Degami\PHPFormsApi\Accessories\FormValues;
use App\Base\Abstracts\Commerce\BaseShippingMethod;
use App\Base\Models\Address;

class FreeShipping extends BaseShippingMethod
{
    /**
     * {@inheritdoc}
     */
    public function getCode() : string
    {
        return 'freeshipping';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Free Shipping';
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Cart $cart): bool
    {
        return $cart->requireShipping() && $cart->getSubTotal() >= App::getInstance()->getSiteData()->getConfigValue('shipping/freeshipping/min_amount');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $form
            ->addField('min_amount', [
                'type' => 'number',
                'title' => 'Minimum Cart Amount to get free shipping',
                'min' => 0,
                'max' => PHP_INT_MAX,
                'step' => 0.1,
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('shipping/freeshipping/min_amount'),
            ]);
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state) : FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('freeshipping', [
            'type' => 'seamless_container',
        ]);

        $out->addField('shipping_costs', [
            'type' => 'markup',
            'value' => App::getInstance()->getUtils()->translate("Your cart is eligible to Free Shipping"),
        ]);

        return $out;
    }

    public function evaluateShippingCosts(Address $shippingAddress, Cart $cart) : float
    {
        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateShipping(?FormValues $values, Cart $cart) : array
    {
        return [
            'shipping_cost' => 0, 
            'additional_data' => ['calculate_when' => time()]
        ];
    }
}