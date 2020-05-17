<?php $this->layout('frontend::layout', ['title' => 'Forgot Password'] + get_defined_vars()) ?>

<div class="container text-center">
    <?php if (isset($result)) : ?>
        <?php print $result;?>
    <?php endif;?>
    <?php if (isset($form)) : ?>
        <?php print $form;?>
    <?php endif;?>
</div>

