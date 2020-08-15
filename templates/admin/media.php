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
    <div class="text-center p-3"><?= $media_elem->getThumb('600x300');?></div>
    <ul><li><?= implode('</li><li>', $elem_data); ?></li></ul>
    <hr />
    <div class="title"><strong><?= $this->sitebase()->translate('Pages');?></strong></div>
    <ul>
    <?php foreach ($pages as $key => $page) : ?>
        <li><a href="<?= $page['url'];?>" target="_blank"><?= $page['title'];?></a> (id: <?= $page['id'];?>)</li>
    <?php endforeach; ?>
    </ul>
    <hr />
    <a class="btn btn-light btn-sm" href="<?= $controller->getControllerUrl();?>?action=list"><?php $this->sitebase()->drawIcon('rewind'); ?> <?= $this->sitebase()->translate('Back');?></a>    
<?php else : ?>
    <?= $form; ?>
<?php endif; ?>
