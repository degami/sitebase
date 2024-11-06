<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<div>
<?= $this->sitebase()->renderAdminTable($table_contents, $table_header); ?>
</div>

<div><?= $total; ?></div>