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

namespace App\Base\Interfaces\Commerce;

use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Cart;
use Degami\PHPFormsApi\Accessories\FormValues;

interface ShippingMethodInterface {

    public function getName() : string;
    public function getCode() : string;
    public function isActive(Cart $cart) : bool;
    public function isApplicable(Cart $cart) : bool;
    public function getShippingFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state) : FAPI\Interfaces\FieldsContainerInterface;
    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form;

    /**
     * return array must contain the following properties
     * 
     * shipping_cost
     * additional_data - mixed
     */
    public function calculateShipping(?FormValues $values, Cart $cart) : array;
}