<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $order \App\Base\Models\Order
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Typ
 */

$this->layout('frontend::layout', ['title' => 'Order Confirmed'] + get_defined_vars()) ?>

<h2><?= $this->sitebase()->translate('Order Confirmed'); ?></h2>
<p><?= $this->sitebase()->translate('Your Order Number is %s', [$order->getOrderNumber()]); ?></p>