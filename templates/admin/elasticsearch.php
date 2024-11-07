<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<div class="row">
    <div class="col-12">
        <div class="jumbotron p-4"><pre class="p-0 m-0"><?= $info;?></pre></div>
        <?= $this->sitebase()->renderAdminTable($table_contents, $table_header); ?>
        <div><?= $total; ?></div>
    </div>
</div>