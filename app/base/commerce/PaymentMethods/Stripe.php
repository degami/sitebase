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
use Stripe\Stripe as StripeMethod;
use Stripe\PaymentIntent;

class Stripe implements PaymentMethodInterface
{
    public function getCode() : string
    {
        return 'stripe';
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function isActive(Cart $cart): bool
    {
        return App::getInstance()->getSiteData()->getConfigValue('payments/stripe/active') == true;
    }

    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $form
            ->addField('is_live', [
                'type' => 'switchbox',
                'title' => 'Is Live',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/stripe/is_live'),
            ])
            ->addField('private_key', [
                'type' => 'textfield',
                'title' => 'Private Key',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/stripe/private_key'),
            ])
            ->addField('public_key', [
                'type' => 'textfield',
                'title' => 'Public Key',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/stripe/public_key'),
            ]);

        return $form;
    }

    public function getPaymentFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state): FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('stripe', [
            'type' => 'seamless_container',
        ]);

        $out->addField('stripe_token', [
            'type' => 'hidden',
            'required' => true,
        ]);

        $out->addMarkup('<div id="card-element"></div><div id="card-errors" style="color:red;margin-top:5px;"></div>');

        $out->addMarkup('<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const stripe = Stripe("' . App::getInstance()->getSiteData()->getConfigValue('payments/stripe/public_key') . '");
    const elements = stripe.elements();
    const card = elements.create("card");
    card.mount("#card-element");

    const form = document.querySelector("#payment-form");

    form.addEventListener("submit", async function(event) {
        if ($(\'#selected_payment_method\', form).val() != \'stripe\') {
            return; // not using stripe
        }

        if ($(\'#stripe_token\', form).val()) {
            return; // already got token
        }
        event.preventDefault();

        const { error, paymentMethod } = await stripe.createPaymentMethod({
            type: "card",
            card: card
        });

        if (error) {
            document.getElementById("card-errors").textContent = error.message;
        } else {
            $(\'#stripe_token\', form).val(paymentMethod.id);
            form.submit();
        }
    });
});
</script>');

        return $out;
    }

    public function executePayment(?FormValues $values, Cart $cart): array
    {
        $hasLang = !empty(App::getInstance()->getAppRouteInfo()?->getVar('lang'));

        $returnUrl =  App::getInstance()->getWebRouter()->getUrl('frontend.commerce.checkout.stripe.stripereturncallback');
        if ($hasLang) {
            $returnUrl =  App::getInstance()->getWebRouter()->getUrl('frontend.commerce.checkout.stripe.stripereturncallback.withlang', ['lang' => App::getInstance()->getCurrentLocale()]);
        }

        StripeMethod::setApiKey(App::getInstance()->getSiteData()->getConfigValue('payments/stripe/private_key'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => intval($cart->getTotalInclTax() * 100), // in centesimi
                'currency' => 'eur',
                'payment_method' => $values->stripe_token,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => $returnUrl,
            ]);

            return [
                'status' => $paymentIntent->status === 'succeeded' ? OrderStatus::PAID : OrderStatus::NOT_PAID,
                'transaction_id' => $paymentIntent->id,
                'additional_data' => $paymentIntent->toArray(),
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
