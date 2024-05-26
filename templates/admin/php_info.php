<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $last_beat string
 * @var $table string
 * @var $paginator string
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<style><?= $style; ?></style>
<?= $php_info; ?>