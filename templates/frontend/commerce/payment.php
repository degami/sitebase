<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $cart \App\Base\Models\Cart
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Payment
 */

$this->layout('frontend::layout', ['title' => 'Payment'] + get_defined_vars()) ?>
    <h2><?= $this->sitebase()->translate('Order Review & Payment') ?></h2>
    <div class="row">
        <div class="col-md-9">
            <div class="card mt-3">
                <div class="card-header"><h6 class="cart-title"><?= $this->sitebase()->translate('Billing:') ?></h6></div>
                <div class="card-body">
                    <?= $cart->getBillingAddress()->getFullContact() ?><br />
                    <?= $cart->getBillingAddress()->getFullAddress() ?>
                </div>
            </div>

        <?php if ($cart->requireShipping()) : ?>
            <div class="card mt-3">
                <div class="card-header"><h6 class="cart-title"><?= $this->sitebase()->translate('Shipping:') ?></h6></div>
                <div class="card-body">
                    <?= $cart->getShippingAddress()->getFullContact() ?><br />
                    <?= $cart->getShippingAddress()->getFullAddress() ?>
                </div>
            </div>
        <?php endif ;?>
        </div>

        <div class="col-md-3">

            <div class="card mt-3">
                <div class="card-header"><h6 class="cart-title"><?= $this->sitebase()->translate('Summary:') ?></h6></div>
                <div class="card-body text-right">

                    <div class="mt-1">
                        <strong class="cart-summary"><?= $this->sitebase()->translate('Cart Subtotal:') ?></strong>
                        <?= $this->sitebase()->formatPrice($cart->getSubTotal(), $cart->getCurrencyCode()) ?>
                    </div>

                    <?php if (abs($cart->getDiscountAmount()) > 0): ?>
                        <div class="mt-1">
                            <strong class="cart-summary"><?= $this->sitebase()->translate('Discount Amount:') ?></strong>
                            <?= $this->sitebase()->formatPrice($cart->getDiscountAmount(), $cart->getCurrencyCode()) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($cart->getTaxAmount() > 0): ?>
                        <div class="mt-1">
                            <strong class="cart-summary"><?= $this->sitebase()->translate('Tax Amount:') ?></strong>
                            <?= $this->sitebase()->formatPrice($cart->getTaxAmount(), $cart->getCurrencyCode()) ?>
                        </div class="mt-1">
                    <?php endif; ?>

                    <?php if ($cart->getShippingAmount() > 0): ?>
                        <div class="mt-1">
                            <strong class="cart-summary"><?= $this->sitebase()->translate('Shipping Amount:') ?></strong>
                            <?= $this->sitebase()->formatPrice($cart->getShippingAmount(), $cart->getCurrencyCode()) ?>
                        </div class="mt-1">
                    <?php endif; ?>

                    <div class="mt-1 mb-2">
                        <strong class="cart-summary"><?= $this->sitebase()->translate('Cart Total:') ?></strong>
                        <span id="cart-total"><?= $this->sitebase()->formatPrice($cart->getTotalInclTax(), $cart->getCurrencyCode()) ?></span>
                    </div>


                </div>
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
        <?= $form ?>
        </div>
    </div>
