<?php
/**
 * @var $title string
 */
$this->layout('base::layout', ['title' => $title] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="/css/site.css">
<?php if ($this->sitebase()->canDebug()) :?>
<script src="<?= $this->sitebase()->assetUrl('/js/debugbar-EnvironmentWidget.js') ?>" type="text/javascript"></script>
<?php endif; ?>
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<section class="jumbotron text-center">
    <div class="container">
        <?= $this->section('content'); ?>
    </div>
</section>