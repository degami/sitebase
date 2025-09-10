<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $cart \App\Base\Models\Cart
 * @var $checkoutURL string
 * @var $applyDiscountUrl string
 * @var $discounts array
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Cart
 */

$this->layout('frontend::layout', ['title' => 'Cart'] + get_defined_vars()) ?>

<h2><?= $this->sitebase()->translate('Cart') ?></h2>

<?php if (count($cart?->getItems() ?? []) > 0): ?>
    <div class="row">
        <div class="col-md-9">
            <?= $form ?>
            <hr />

            <div class="text-right mb-2">

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
                    <?= $this->sitebase()->formatPrice($cart->getTotalInclTax(), $cart->getCurrencyCode()) ?>
                </div>

            </div>
             <a href="<?= $checkoutURL?>" class="btn btn-primary">
                <?= $this->sitebase()->translate('Checkout') ?>
            </a>

        </div>

        <div class="col-md-3">
            <h5><?= $this->sitebase()->translate('Apply Discount Code') ?></h5>

            <form method="get" id="applydiscount_form" action="<?= $applyDiscountUrl ?>">
                <input type="hidden" name="action" value="apply_discount">

                <div class="d-flex justify-content-between align-items-center">
                    <input type="text" name="discount_code" id="discount_code" class="form-control d-inline mr-2" required>
                    <button type="submit" class="btn btn-primary d-inline-block text-nowrap"><?= $this->sitebase()->translate('Apply') ?></button>
                </div>

            </form>

            <hr />

            <h5><?= $this->sitebase()->translate('Applied Discounts:') ?></h5>
            <?php foreach ($discounts ?? [] as $discount): ?>
                <?php /** @var \App\Base\Models\CartDiscount $discount */ ?>
                <div class="alert alert-info d-flex">
                    <strong class="mr-1"><?= $discount['model']->getInitialDiscount()?->getTitle() ?></strong>
                    <?= $this->sitebase()->translate('Amount:') ?> <?= $this->sitebase()->formatPrice($discount['model']->getDiscountAmount(), $cart->getCurrencyCode()) ?>
                    <a href="<?= $discount['removeUrl'] ?>" class="ml-auto text-danger">
                        <?php $this->sitebase()->drawIcon('x-circle') ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


<?php else: ?>
    <p><?= $this->sitebase()->translate('Your cart is empty.') ?></p>
<?php endif; ?>