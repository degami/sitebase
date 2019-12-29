<?php
$this->layout('base::html', ['title' => $title] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/site.css');?>">
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<?php $this->start('scripts') ?>
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/js/site.js');?>"></script>
<?= $this->section('scripts'); ?>
<?php $this->stop() ?>

<div class="container-fluid">
    <div class="menu">
        <?= $this->sitebase()->renderBlocks('pre_menu', $controller); ?>
        <?= $this->section('menu'); ?>
        <?= $this->sitebase()->renderBlocks('post_menu', $controller); ?>
    </div>

    <div class="header">
        <?= $this->sitebase()->renderBlocks('pre_header', $controller); ?>
        <?= $this->section('header'); ?>
        <?= $this->sitebase()->renderBlocks('post_header', $controller); ?>
    </div>

    <?= $this->sitebase()->renderBlocks('pre_content', $controller); ?>
    <?= $this->section('content'); ?>
    <?= $this->sitebase()->renderBlocks('post_content', $controller); ?>

    <div class="footer">
        <?= $this->sitebase()->renderBlocks('pre_footer', $controller); ?>
        <?= $this->section('footer'); ?>
        <?= $this->sitebase()->renderBlocks('post_footer', $controller); ?>
    </div>
</div>
