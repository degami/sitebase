<?php
/**
 * @var \App\Base\Abstracts\Controllers\BaseHtmlPage $controller
 * @var \Degami\PHPFormsApi\Form $form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<div>
<?= $form; ?>
</div>