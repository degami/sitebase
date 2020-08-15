<?php
/**
 * @var $allowedMethods array
 */
$this->layout('errors::error_page', ['title' => 'Method Not Allowed!'] + get_defined_vars()) ?>

<h1 class="jumbotron-heading">405!</h1>
<p class="lead text-muted">Sorry, method not allowed.</p>
<?php if (getenv('DEBUG')) :?>
<p>allowed methods are: <?= implode(", ", array_map('strtoupper', $allowedMethods)); ?></p>
<?php endif; ?>