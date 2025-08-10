<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $order \App\Base\Models\Order
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Typ
 */

$this->layout('frontend::layout', ['title' => 'Order Confirmed'] + get_defined_vars()) ?>

<h2>Order Confirmed</h2>
<p>Your Order Number is <?= $order->getOrderNumber() ?></p>