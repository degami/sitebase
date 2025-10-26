<?php
/**
 * @var $title string
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 */
$this->layout('base::layout', ['title' => $title] + get_defined_vars()) ?>

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
            <?php if ($region == 'content') :?>
                <?php $this->sitebase()->renderFlashMessages($controller); ?>
            <?php endif;?>

            <?php if ($region != 'menu') : ?>
            <div class="pre-<?= $region;?>">
            <?php endif;?>
                <?= $this->sitebase()->renderBlocks('pre_'.$region, $controller); ?>
            <?php if ($region != 'menu') : ?>
            </div>
            <div class="content-<?= $region;?>">
            <?php endif;?>
                <?= $this->section($region); ?>
            <?php if ($region != 'menu') : ?>
            </div>
            <div class="post-<?= $region;?>">
            <?php endif; ?>
                <?= $this->sitebase()->renderBlocks('post_'.$region, $controller); ?>
            <?php if ($region != 'menu') : ?>
            </div>
            <?php endif;?>
        </div>
    <?php endforeach; ?>
</div>
