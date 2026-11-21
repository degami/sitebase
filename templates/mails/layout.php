<?php
/**
 * @var string $subject
 * @var string $site_logo
 */

$this->layout('base::mail', get_defined_vars()) ?>

<?php $this->start('head') ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/site.css');?>">
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<?= $site_logo; ?>

<?= $this->section('content'); ?>
