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

class BankTransfer implements PaymentMethodInterface
{
    public function getCode() : string
    {
        return 'bank_transfer';
    }

    public function getName(): string
    {
        return 'Bank Transfer';
    }

    public function isActive(Cart $cart): bool
    {
        return App::getInstance()->getSiteData()->getConfigValue('payments/bank_transfer/active') == true;
    }

    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $form
            ->addField('iban', [
                'type' => 'textfield',
                'title' => 'IBAN',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/bank_transfer/iban'),
            ])
            ->addField('reason', [
                'type' => 'textarea',
                'title' => 'Reason for bank transfer',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/bank_transfer/reason'),
            ]);

        return $form;
    }

    public function getPaymentFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state): FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('banktransfer', [
            'type' => 'seamless_container',
        ]);

        $paymentInfo = '<div>' . App::getInstance()->getUtils()->translate('Please make the bank transfer using the following IBAN') . ': <strong>' . App::getInstance()->getSiteData()->getConfigValue('payments/bank_transfer/iban') . '</strong></div>';
        if ($reason = App::getInstance()->getSiteData()->getConfigValue('payments/bank_transfer/reason')) {
            $paymentInfo .= '<div>' . App::getInstance()->getUtils()->translate('Specify the following bank transfer reason') . ': <strong>' . $reason . '</strong></div>';
        }

        $out->addMarkup($paymentInfo);

        $out->addField('transfer_reference', [
            'type' => 'textfield',
            'title' => 'Transfer Reference Code',
        ]);

        return $out;
    }

    public function executePayment(?FormValues $values, Cart $cart): array
    {
        return [
            'status' => OrderStatus::WAITING_FOR_PAYMENT, // verrà marcato come pagato manualmente
            'transaction_id' => $values->transfer_reference,
            'additional_data' => [
                'note' => 'Wait for confirmation of the transfer',
                'iban' => App::getInstance()->getSiteData()->getConfigValue('payments/bank_transfer/iban'),
                'reason' => App::getInstance()->getSiteData()->getConfigValue('payments/bank_transfer/reason'),
            ]
        ];
    }
}
