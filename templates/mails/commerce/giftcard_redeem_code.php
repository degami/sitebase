<?php
/**
 * @var string $subject
 * @var \App\Base\Models\Order $order
 * @var \App\Base\Models\OrderPayment $payment
 * @var \App\Base\Models\Website $website
 */

$orderItems = $order->getItems();
$orderNumber = $order->getOrderNumber();

$this->layout('mails::layout', get_defined_vars()) ?>

<p><?= $this->sitebase()->translate('Hello'); ?> <?= $order->getBillingAddress()->getFullName(); ?></p>

<p><?= $this->sitebase()->translate('Thanks for your purchase on %s', [$website->getSiteName()]);?></p>
<p><?= $this->sitebase()->translate('Here\'s your giftcard redeem code: <strong>%s</strong>', [$redeem_code]); ?></p>
<p><?= $this->sitebase()->translate('You can redeem it in your account area'); ?></p>

<p><?= $this->sitebase()->translate('Best regards, the %s team', [$website->getSiteName()]); ?></p>