<?php $this->layout('frontend::layout', ['title' => $object->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<meta name="description" content="<?= $object->meta_description;?>">
<meta name="keywords" content="<?= $object->meta_keywords;?>">
<link rel="canonical" href="<?= $object->getFrontendUrl();?>" />
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="page-title"><?php echo $object->getTitle();?></h1>
<div class="row">
    <?php if (!empty($object->content)) :?>
    <div class="col-6">
        <div class="page-content"><?php echo $object->getContent();?></div>
    </div>
    <?php endif;?>
    <div class="col-<?= (!empty($object->getContent())) ? '6': '12';?>">
        <div class="contact-form"><?php echo $form;?></div>        
    </div>
</div>
