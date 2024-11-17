<?php
/**
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('frontend::layout', ['title' => 'Login'] + get_defined_vars()) ?>
<?php $this->start('menu') ?><?php $this->stop() ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/admin.css');?>">
<?php $this->stop() ?>


<div class="container text-center"><?php print $form;?></div>

<?php /* if ($form->isSubmitted()) :?>
    <?php var_dump($form->getValues()); ?>
<?php else : ?>
    <div class="container text-center"><?php print $form;?></div>
<?php endif; */ ?>
