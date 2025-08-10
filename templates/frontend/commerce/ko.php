<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $order \App\Base\Models\Order
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Ko
 */

$this->layout('frontend::layout', ['title' => 'Order not confirmed'] + get_defined_vars()) ?>

<h2>Order not confirmed</h2>
<p>There was an issue with your order. Please try again later or contact support.</p