<?php
/**
 * @var $e Exception
 */
$this->layout('errors::error_page', ['title' => 'Exception!'] + get_defined_vars()) ?>

<h1 class="jumbotron-heading">Exception!</h1>
<?php if (getenv('DEBUG') && $e instanceof Exception) :?>
<h4><?= $e->getMessage(); ?></h4>
<p class="lead text-muted">Trace:</p>
<pre class="text-left"><?= $e->getTraceAsString(); ?></pre>
<?php endif; ?>
