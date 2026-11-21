<?php
/**
 * @var \App\Base\Abstracts\Controllers\BaseHtmlPage $controller
 * @var string $action
 * @var string $listing
 * @var string $before_listing
 * @var string $paginator
 * @var \Degami\PHPFormsApi\Form $form
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
