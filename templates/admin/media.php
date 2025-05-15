<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $table string
 * @var $paginator string
 * @var $media_elem \App\Site\Models\MediaElement
 * @var $elem_data array
 * @var $pages array
 * @var $form \Degami\PHPFormsApi\Form
 * @var $submission \App\Site\Models\ContactSubmission
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<?php if ($action == 'list') : ?>
    <div class="table-responsive">
        <?= $table; ?>
    </div>
    <?= $paginator; ?>
<?php elseif ($action == 'usage') : ?>
    <div class="container">
        <h3 class="text-center m-3"><?= $this->sitebase()->translate('Media Element Usage');?></h3>
        <hr class="border border-primary border-2 opacity-50" />
        <div class="row">
            <div class="col-md-6">
                <div class="text-center p-3"><?= $media_elem->getThumb('600x300');?></div>
                <ul class="list-group m-0 p-3"><li class="list-group-item"><?= implode('</li><li class="list-group-item">', $elem_data); ?></li></ul>
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
