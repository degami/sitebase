<?php $this->layout('frontend::layout', ['title' => $object->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<meta name="description" content="<?= $object->meta_description;?>">
<meta name="keywords" content="<?= $object->meta_keywords;?>">
<link rel="canonical" href="<?= $object->getFrontendUrl();?>" />
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="page-title"><?php echo $object->getTitle();?></h1>
<div class="page-content"><?php echo $object->getContent();?></div>

<?php if (($pages = $object->getPages()) && count($pages)) : ?>
<div class="taxonomy-pages">
    <ul class="list">
    <?php foreach ($pages as $page) : ?>
    <li>
        <a href="<?= $page->getFrontendUrl();?>"><?= $page->getTitle(); ?></a>
    </li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?= $this->section('content'); ?>
