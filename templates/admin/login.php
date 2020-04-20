<?php $this->layout('frontend::layout', ['title' => 'Login'] + get_defined_vars()) ?>
<?php $this->start('menu') ?><?php $this->stop() ?>

<?php $this->start('head') ?>
<?php $this->stop() ?>


<div class="container text-center"><?php print $form;?></div>

<?php /* if ($form->isSubmitted()) :?>
    <?php var_dump($form->getValues()); ?>
<?php else : ?>
    <div class="container text-center"><?php print $form;?></div>
<?php endif; */ ?>
