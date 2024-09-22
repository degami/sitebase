<?php

$this->layout('base::html', ['title' => 'Sitebase'] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/site.css');?>">
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<?php $this->start('scripts') ?>
<?= $this->section('scripts'); ?>
<?php $this->stop() ?>

<div id="app" class="container-fluid">
    <!-- Punto di montaggio di Vue.js -->
</div>
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/vue_theme/js/bundle.js');?>"></script>
