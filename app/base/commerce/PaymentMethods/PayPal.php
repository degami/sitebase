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
use Degami\PHPFormsApi as FAPI;
use Degami\PHPFormsApi\Containers\SeamlessContainer;
use App\Base\Models\Cart;
use App\Base\Models\OrderStatus;
use Degami\PHPFormsApi\Accessories\FormValues;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\PayPalEnvironment;
use App\Base\Abstracts\Commerce\BasePaymentMethod;

class PayPal extends BasePaymentMethod
{
    public function getCode() : string
    {
        return 'paypal';
    }

    public function getName(): string
    {
        return 'PayPal';
    }

    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $form
            ->addField('is_live', [
                'type' => 'switchbox',
                'title' => 'Is Live',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/paypal/is_live'),
            ])
            ->addField('client_id', [
                'type' => 'textfield',
                'title' => 'Client ID',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/paypal/client_id'),
            ])
            ->addField('client_secret', [
                'type' => 'textfield',
                'title' => 'Client Secret',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/paypal/client_secret'),
            ]);

        return $form;
    }

    public function getPaymentFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state): FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('paypal', [
            'type' => 'seamless_container',
        ]);

        // Campo hidden per ricevere order_id
        $out->addField('paypal_order_id', [
            'type' => 'hidden',
        ]);

        // Output JS SDK + pulsante
        $out->addMarkup('
            <div id="paypal-button-container"></div>
            <script src="https://www.paypal.com/sdk/js?client-id=' . App::getInstance()->getSiteData()->getConfigValue('payments/paypal/client_id') . '&currency=EUR"></script>
            <script>
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: "' . number_format($cart->getTotalInclTax(), 2, '.', '') . '"
                                }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        $("#paypal_order_id").val(data.orderID);
                        $("#'.$form->getFormId().'").submit();
                    }
                }).render("#paypal-button-container");
            </script>
        ');

        return $out;
    }

    public function executePayment(?FormValues $values, Cart $cart): array
    {
        $clientId = App::getInstance()->getSiteData()->getConfigValue('payments/paypal/client_id');
        $clientSecret = App::getInstance()->getSiteData()->getConfigValue('payments/paypal/client_secret');
        $isLive = App::getInstance()->getSiteData()->getConfigValue('payments/paypal/is_live');
        $environment = new SandboxEnvironment($clientId, $clientSecret);
        if ($isLive) {
            $environment = new PayPalEnvironment($clientId, $clientSecret);
        }
        $client = new PayPalHttpClient($environment);

        $orderId = $values->paypal_order_id;

        if (!$orderId) {
            return [
                'status' => OrderStatus::NOT_PAID,
                'transaction_id' => null,
                'additional_data' => ['error' => 'Missing PayPal Order ID']
            ];
        }

        $request = new OrdersCaptureRequest($orderId);
        $request->prefer('return=representation');

        try {
            $response = $client->execute($request);
            $status = $response->result->status;

            return [
                'status' => $status === 'COMPLETED' ? OrderStatus::PAID : OrderStatus::NOT_PAID,
                'transaction_id' => $response->result->id,
                'additional_data' => [
                    'payer' => $response->result->payer ?? null,
                    'purchase_units' => $response->result->purchase_units ?? null,
                    'status' => $status,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => OrderStatus::NOT_PAID,
                'transaction_id' => null,
                'additional_data' => ['error' => $e->getMessage()],
            ];
        }
    }
}
