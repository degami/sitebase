<?php
/**
 * @var $title string
 */
$this->layout('base::page', ['title' => $title] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="/css/site.css">
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<section class="jumbotron text-center">
    <div class="container">
        <?= $this->section('content'); ?>
    </div>
</section>