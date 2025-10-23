<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $table string
 * @var $before_table string
 * @var $paginator string
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<?php if ($action == 'list') : ?>
    <?= $before_table; ?>
    <div class="table-responsive">
        <?= $table; ?>
    </div>
    <?= $paginator ?? ''; ?>
<?php else : ?>
    <?= $form; ?>
<?php endif; ?>
