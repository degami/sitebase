<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars());

echo $form;
