<?php
/**
 * @var $object \App\Base\Abstracts\Models\FrontendModel
 */
$this->layout('frontend::layout', ['title' => $object->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="canonical" href="<?= $object->getFrontendUrl();?>" />
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="news-title"><?php echo $object->getTitle();?></h1>
<div class="news-date"><?php echo $object->getDate();?></div>
<div class="news-content"><?php echo $object->getContent();?></div>

<?= $this->section('content'); ?>
