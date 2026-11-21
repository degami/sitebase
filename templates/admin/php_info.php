<?php
/**
 * @var \App\Base\Abstracts\Controllers\BaseHtmlPage $controller
 * @var string $style
 * @var string $php_info
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<style><?= $style; ?></style>
<?= $php_info; ?>