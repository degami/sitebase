<?php
/**
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('frontend::layout', ['title' => 'Login'] + get_defined_vars()) ?>

<div class="container text-center">
    <?php print $form;?>
    <p>
        <a href="<?= $this->sitebase()->getUrl('frontend.users.passwordforgot')?>"><?= $this->sitebase()->translate('Forgot your password?');?></a>
    </p>
</div>

<?php /* if ($form->isSubmitted()) :?>
    <?php var_dump($form->getValues()); ?>
<?php else : ?>
    <div class="container text-center"><?php print $form;?></div>
<?php endif; */ ?>
