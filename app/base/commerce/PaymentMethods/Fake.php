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

namespace App\Base\Commerce\PaymentMethods;

use App\App;
use App\Base\Interfaces\Commerce\PaymentMethodInterface;
use Degami\PHPFormsApi as FAPI;
use Degami\PHPFormsApi\Containers\SeamlessContainer;
use App\Base\Models\Cart;
use App\Base\Models\OrderStatus;
use Degami\PHPFormsApi\Accessories\FormValues;

class Fake implements PaymentMethodInterface
{
    public function getCode() : string
    {
        return 'fake';
    }

    public function getName(): string
    {
        return 'Fake (Test) Payment';
    }

    public function isActive(Cart $cart): bool
    {
        return App::getInstance()->getSiteData()->getConfigValue('payments/fake/active') == true && App::getInstance()->getEnvironment()->canDebug();
    }

    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        return $form;
    }

    public function getPaymentFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state) : FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('fake', [
            'type' => 'seamless_container',
        ]);

        $out->addField('order_status', [
            'type' => 'select',
            'title' => 'Order Confirmation',
            'options' => [
                '0' => 'KO',
                '1' => 'OK',
            ],
        ]);

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function executePayment(?FormValues $values, Cart $cart) : array
    {
        return [
            'status' => $values->order_status == 1 ? OrderStatus::PAID : OrderStatus::NOT_PAID, 
            'transaction_id' => 'fake_payment_'.uniqid(), 
            'additional_data' => ['description' => 'This order is a test', 'cart_id' => $cart->getId(), 'when' => time()]
        ];
    }
}