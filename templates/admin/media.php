<?php
/**
 * @var \App\Base\Abstracts\Controllers\BaseHtmlPage $controller
 * @var string $action
 * @var string $before_listing
 * @var string $listing
 * @var string $paginator
 * @var \App\Site\Models\MediaElement $media_elem
 * @var array $elem_data
 * @var array $pages
 * @var \Degami\PHPFormsApi\Form $form
 * @var \App\Site\Models\ContactSubmission $submission
 * @var string $info_table
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<?php if ($action == 'list') : ?>
    <?= $before_listing ?? ''; ?>
    <div class="table-responsive">
        <?= $listing; ?>
    </div>
    <?= $paginator ?? ''; ?>
<?php elseif ($action == 'usage') : ?>
    <div class="container">
        <h3 class="text-center m-3"><?= $this->sitebase()->translate('Media Element Usage');?></h3>
        <hr class="border border-primary border-2 opacity-50" />
        <div class="row">
            <div clas4s="col-md-6">
                <?= $info_table ;?> 
            </div>
            <div class="col-md-6 text-left">
                <p class="m-0 p-0 text-center"><?= $this->sitebase()->translate('This media element is used in the following pages:');?></p>
                <ul class="list-group m-0 p-3">
                <?php foreach ($pages as $key => $page) : ?>
                    <li class="list-group-item"><a href="<?= $page['url'];?>" target="_blank"><?= $page['title'];?></a> (id: <?= $page['id'];?>)</li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php else : ?>
    <?= $form; ?>
<?php endif; ?>
