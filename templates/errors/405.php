<?php
/**
 * @var $allowedMethods array
 */
$this->layout('errors::error_page', ['title' => 'Method Not Allowed!'] + get_defined_vars()) ?>

<img class="img-fluid" src="<?= $this->sitebase()->assetUrl('/svg_errors/405.svg');?>" />
<h1 class="jumbotron-heading">405!</h1>
<p class="lead text-muted">Sorry, method not allowed.</p>
<?php if ($this->sitebase()->getEnvironment()->canDebug()) :?>
<p>allowed methods are: <?= implode(", ", array_map('strtoupper', (array)$allowedMethods)); ?></p>
<?php endif; ?>