<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $cart \App\Base\Models\Cart
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Shipping
 */

$this->layout('frontend::layout', ['title' => 'Shipping'] + get_defined_vars()) ?>

<h1><?= $this->sitebase()->translate('Shipping Address'); ?></h1>
<?= $form ?>