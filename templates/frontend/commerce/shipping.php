<?php
/**
 * @var \App\Base\Abstracts\Models\AccountModel $user
 * @var \App\Base\Models\Cart $cart
 * @var \App\Base\Controllers\Frontend\Commerce\Checkout\Shipping $controller
 * @var \Degami\PHPFormsApi\Form $form
 */
$this->layout('frontend::layout', ['title' => 'Shipping'] + get_defined_vars()) ?>

<h1><?= $this->sitebase()->translate('Shipping Address'); ?></h1>
<?= $form ?>