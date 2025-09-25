<?php

$this->layout('frontend::layout', ['title' => $title ?? ''] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<meta name="description" content="<?= $meta_description ?? '';?>">
<meta name="keywords" content="<?= $meta_keywords ?? '';?>">
<link rel="canonical" href="<?= $meta_canonical ?? '';?>" />
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="page-title"><?= $title ?? ''; ?></h1>
<div class="page-content"><?= $content ?? ''; ?></div>

