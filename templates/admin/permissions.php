<?php
/**
 * @var $form \Degami\PHPFormsApi\Form
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<div>
<?= $form; ?>
</div>