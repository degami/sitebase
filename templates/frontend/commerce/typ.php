<?php
/**
 * @var \App\Base\Abstracts\Models\AccountModel $user
 * @var \App\Base\Models\Order $order
 * @var \App\Base\Controllers\Frontend\Commerce\Checkout\Typ $controller
 */

$this->layout('frontend::layout', ['title' => 'Order Confirmed'] + get_defined_vars()) ?>

<h2><?= $this->sitebase()->translate('Order Confirmed'); ?></h2>
<p><?= $this->sitebase()->translate('Your Order Number is %s', [$order->getOrderNumber()]); ?></p>