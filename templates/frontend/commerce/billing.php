<?php
/**
 * @var $user \App\Base\Abstracts\Models\AccountModel
 * @var $cart \App\Base\Models\Cart
 * @var $controller \App\Base\Controllers\Frontend\Commerce\Billing
 */

$this->layout('frontend::layout', ['title' => 'Billing'] + get_defined_vars()) ?>

<h1><?= $this->sitebase()->translate('Billing Address'); ?></h1>
<?= $form ?>