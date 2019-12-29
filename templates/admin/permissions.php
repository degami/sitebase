<?php
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<div>
<?= $form; ?>
</div>