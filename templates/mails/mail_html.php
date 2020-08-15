<?php
/**
 * @var $subject string
 */
?><!doctype html>
<html lang="<?= $this->sitebase()->getCurrentLocale() ?>">
<head>
    <title><?=$this->e($subject)?></title>
    <meta charset="utf-8">
    <?= $this->section('head'); ?>
</head>
<body>
<?= $this->section('content'); ?>
</body>
</html>