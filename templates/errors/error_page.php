<?php $this->layout('base::html', ['title' => $title] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="/css/site.css">
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<section class="jumbotron text-center">
    <div class="container">
        <?= $this->section('content'); ?>
    </div>
</section>