<?php
/**
 * @var $title string
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 */
$this->layout('base::page', ['title' => $title] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/site.css');?>">
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/fa.css');?>">
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<?php $this->start('scripts') ?>
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/js/site.js');?>"></script>
<?= $this->section('scripts'); ?>
<?php $this->stop() ?>

<div class="container-fluid">
    <?php foreach ($this->sitebase()->getPageRegions() as $region) :?>
        <div class="<?= $region; ?>">
            <?php if ($region == 'content') {
                $this->sitebase()->renderFlashMessages($controller);
            }
            ?>
            <?= $this->sitebase()->renderBlocks('pre_'.$region, $controller); ?>
            <?= $this->section($region); ?>
            <?= $this->sitebase()->renderBlocks('post_'.$region, $controller); ?>
        </div>
    <?php endforeach; ?>
</div>
