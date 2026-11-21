<?php
/**
 * @var \App\Base\Abstracts\Models\AccountModel $user
 * @var \App\Base\Models\Order $order
 * @var string $stripe_public_key
 * @var string $ok_url
 * @var string $ko_url
 * @var \App\Base\Controllers\Frontend\Commerce\Checkout\Stripe\StripeReturnCallback $controller
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
