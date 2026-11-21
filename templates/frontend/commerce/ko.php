<?php
/**
 * @var \App\Base\Abstracts\Models\AccountModel $user
 * @var \App\Base\Models\Order $order
 * @var \App\Base\Controllers\Frontend\Commerce\Checkout\Ko $controller
 */

$this->layout('frontend::layout', ['title' => 'Order not confirmed'] + get_defined_vars()) ?>

<h2><?= $this->sitebase()->translate('Order not confirmed'); ?></h2>
<p><?= $this->sitebase()->translate('There was an issue with your order. Please try again later or contact support.'); ?></p