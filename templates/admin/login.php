<?php
/**
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('frontend::layout', ['title' => 'Login'] + get_defined_vars()) ?>
<?php $this->start('menu') ?><?php $this->stop() ?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/admin.css');?>">
<?php $this->stop() ?>

<div class="container-fluid">
    <div class="row" style="height: 100vh;align-content: center;">
        <div class="col" style="height: 80vh;background-color: #333;align-content: center;">
            <?php print $form;?>
        </div>
        <div class="col-8 d-none d-lg-block" style="position: relative;height: 80vh;background: url(<?= $bgUrl; ?>) center center;background-size: auto;background-size: cover;">
             <div style="
                position: absolute;
                bottom: 10px;
                right: 10px;
                color: white;
                font-size: 0.8rem;
                background: rgba(0,0,0,0.3);
                padding: 2px 6px;
                border-radius: 4px;
            ">
                Photo by <?= $bgAuthor; ?>
            </div>
        </div>
    </div>
</div>

<?php /* if ($form->isSubmitted()) :?>
    <?php var_dump($form->getValues()); ?>
<?php else : ?>
    <div class="container text-center"><?php print $form;?></div>
<?php endif; */ ?>
