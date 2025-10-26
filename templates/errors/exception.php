<?php
/**
 * @var $e Exception
 */
$this->layout('errors::error_page', ['title' => 'Exception!'] + get_defined_vars()) ?>

<img class="img-fluid" src="<?= $this->sitebase()->assetUrl('/svg_errors/exception.svg');?>" />
<h1 class="jumbotron-heading">Exception!</h1>
<?php if ($this->sitebase()->getEnvironment()->canDebug() && $e instanceof Throwable) :?>
<h4><?= $e->getMessage(); ?></h4>
<p class="lead text-muted">Trace:</p>
<pre class="text-left"><?= $e->getTraceAsString(); ?></pre>
<?php endif; ?>
