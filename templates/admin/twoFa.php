<?php
$this->layout('frontend::layout', ['title' => $this->sitebase()->env('APPNAME')] + get_defined_vars()) ?>
<?php $this->start('menu') ?><?php $this->stop() ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/admin.css');?>">
<?php $this->stop() ?>

<div class="page-content">        
        <h2 class="text-center p-3"><?= $pageIntro; ?></h2>
        <div class="container text-center"><?php print $form;?></div>
</div>
