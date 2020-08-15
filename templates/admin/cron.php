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

<?php if ($action == 'list') : ?>
    <?= $last_beat;?>
    <div class="table-responsive">
        <?= $table; ?>
    </div>
    <?= $paginator; ?>
<?php else : ?>
    <?= $form; ?>
<?php endif; ?>
