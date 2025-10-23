<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $last_beat string
 * @var $listing string
 * @var $before_listing string
 * @var $paginator string
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<?php if ($action == 'list') : ?>
   <?= $before_listing ?? ''; ?>
    <div class="table-responsive">
        <?= $listing; ?>
    </div>
    <?= $paginator ?? ''; ?>
<?php else : ?>
    <?= $form; ?>
<?php endif; ?>
