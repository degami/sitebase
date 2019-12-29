<?php
$this->layout('frontend::layout', ['title' => $this->sitebase()->env('APPNAME')] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<meta name="description" content="<?= $object->meta_description;?>">
<meta name="keywords" content="<?= $object->meta_keywords;?>">
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="page-title"><?php echo $this->sitebase()->env('APPNAME');?></h1>
<?php if (($gallery = $object->getGallery()) && count($gallery)) : ?>
<div class="page-gallery">
    <div class="row">
    <?php foreach ($gallery as $image) : ?>
    <div class="col-md-3 col-sm-4 col-xs-6">
        <?php echo $image->getThumb("300x200", null, 'img-fluid img-thumbnail'); ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<div class="page-content"><?php echo $object->content;?></div>
