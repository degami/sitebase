<?php
/**
 * @var $object \App\Base\Abstracts\Models\FrontendModel
 */
$this->layout('frontend::layout', ['title' => $object->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="canonical" href="<?= $object->getFrontendUrl();?>" />
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<div class="row">
    <div class="col-md-10">
        <h1 class="giftcard-title"><?php echo $object->getTitle();?></h1>
        <div class="row">
            <?php if ($object->getMediaId()) : ?>
            <div class="giftcard-image col-2"><?= $object->getMedia()?->getThumb("400x300", class: 'img-fluid'); ?></div>
            <?php endif; ?>
            <div class="giftcard-content col"><?php echo $object->getContent();?></div>
        </div>

        <?php if (($gallery = $object->getGallery()) && count($gallery)) : ?>
        <div class="page-gallery">
            <div class="row gallery">
            <?php foreach ($gallery as $image) : ?>
                <?php echo $image->getThumb("300x200", null, 'img-fluid img-thumbnail', ['data-gallery-id' => 'gallery-'.$object->getId(), "data-gallery-src" => $image->getImageUrl(), 'data-gallery-desc' => $image->getFileName()]); ?>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <div class="col-md-2">

        <?= $form; ?>

    </div>
</div>

<?= $this->section('content'); ?>
