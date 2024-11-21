<?php
/**
 * @var $object \App\Base\Abstracts\Models\FrontendModel
 */
$this->layout('frontend::layout', ['title' => $object->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<meta name="description" content="<?= $object->meta_description;?>">
<meta name="keywords" content="<?= $object->meta_keywords;?>">
<link rel="canonical" href="<?= $object->getFrontendUrl();?>" />
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="page-title"><?php echo $object->getTitle();?></h1>
<div class="page-content"><?php echo $object->getContent();?></div>

<?php if (($gallery = $object->getGallery()) && count($gallery)) : ?>
<div class="page-gallery">
    <div class="row gallery">
    <?php foreach ($gallery as $image) : ?>
        <?php echo $image->getThumb("300x200", null, 'img-fluid img-thumbnail', ['data-gallery-id' => 'gallery-'.$object->getId(), "data-gallery-src" => $image->getImageUrl(), 'data-gallery-desc' => $image->getFileName()]); ?>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?= $this->section('content'); ?>
