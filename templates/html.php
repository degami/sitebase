<?php
/** @var \DebugBar\StandardDebugBar $debugbar */
$debugbar = $this->sitebase()->getDebugbar();
$debugbarRenderer = $debugbar->getJavascriptRenderer();
if (!isset($body_class)) {
    $body_class = "";
}
?><!doctype html>
<html lang="<?= $this->sitebase()->getCurrentLocale() ?>">
<head>
    <title><?=$this->e($title)?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/bootstrap/css/bootstrap.min.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/jqueryui/themes/base/all.css');?>">
    <script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/jquery/jquery.min.js');?>"></script>
    <?= getenv('DEBUG') ? $debugbarRenderer->renderHead() : ''; ?>
    <?= $this->section('head'); ?>
    <?= $this->section('head_scripts'); ?>
</head>
<body class="<?= $body_class;?>">
<?= $this->sitebase()->renderBlocks('after_body_open', $controller); ?>
<?= $this->section('content'); ?>
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/jqueryui/jquery-ui.min.js');?>"></script>
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/bootstrap/js/bootstrap.min.js');?>"></script>
<?= $this->section('scripts'); ?>
<?= $this->section('styles'); ?>
<?= getenv('DEBUG') ? $debugbarRenderer->render() : '' ?>
<?= $this->sitebase()->renderBlocks('before_body_close', $controller); ?>
</body>
</html>