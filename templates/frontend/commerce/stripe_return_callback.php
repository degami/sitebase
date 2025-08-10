<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $order \App\Base\Models\Order
 * @var $stripe_public_key string
 * @var $ok_url string
 * @var $ko_url string
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Checkout\StripeReturnCallback
 */

$this->layout('frontend::layout', ['title' => 'Stripe Return'] + get_defined_vars()) ?>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?= $stripe_public_key ?>');

const clientSecret = new URLSearchParams(window.location.search).get("payment_intent_client_secret");
stripe.retrievePaymentIntent(clientSecret).then(({paymentIntent}) => {
  if (paymentIntent.status === "succeeded") {
    window.location.href = "<?= $ok_url ?>"; // thank you page
  } else {
    window.location.href = "<?= $ko_url ?>"; // errore
  }
});
</script>
