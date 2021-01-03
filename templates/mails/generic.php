<?php
/**
 * @var string $subject
 * @var string $body
 */
$this->layout('mails::mail_html', get_defined_vars()) ?>

<?php $this->start('head') ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/site.css');?>">
<?= $this->section('head'); ?>
<?php $this->stop() ?>


<?= $body; ?>

<?= $this->section('content'); ?>
<?php
