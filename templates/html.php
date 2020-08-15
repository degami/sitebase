<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $title string
 */
/** @var \DebugBar\StandardDebugBar $debugbar */
$debugbar = $this->sitebase()->getDebugbar();
$debugbarRenderer = $debugbar->getJavascriptRenderer($this->sitebase()->getUrl('frontend.root').'debugbar');
if (!isset($body_class)) {
    $body_class = "";
}
?><!doctype html>
<html lang="<?= $this->sitebase()->getCurrentLocale() ?>">
<head>
    <title><?= $this->sitebase()->translate($title)?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/bootstrap/css/bootstrap.min.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/jqueryui/themes/base/all.css');?>">
    <script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/jquery/jquery.min.js');?>"></script>
	<meta name="viewport" content="minimum-scale=1.0, maximum-scale=1.0, width=device-width">
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