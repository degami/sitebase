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
use Degami\PHPFormsApi\Accessories\FormValues;
use App\Base\Models\OrderStatus;

class Cod implements PaymentMethodInterface
{
    public function getCode() : string
    {
        return 'cod';
    }

    public function getName(): string
    {
        return 'Cash on Delivery';
    }

    public function isActive(Cart $cart): bool
    {
        return App::getInstance()->getSiteData()->getConfigValue('payments/cod/active') == true;
    }

    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        return $form;
    }

    public function getPaymentFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state) : FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('cod', [
            'type' => 'seamless_container',
        ]);

        // Campo di contatto facoltativo
        $out->addField('cod_contact', [
            'type' => 'textfield',
            'title' => 'Phone nr.',
            'description' => 'For any communications upon delivery',
            'required' => false,
        ]);

        // Campo note per la consegna
        $out->addField('cod_notes', [
            'type' => 'textarea',
            'title' => 'Notes for delivery',
            'required' => false,
        ]);

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function executePayment(?FormValues $values, Cart $cart) : array
    {
        return [
            'status' => OrderStatus::WAITING_FOR_PAYMENT, 
            'transaction_id' => 'cod_payment:' . uniqid(), 
            'additional_data' => [
                'contact' => $values->cod_contact,
                'notes' => $values->cod_notes,
            ],
        ];
    }
}