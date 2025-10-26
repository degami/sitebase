<?php $this->layout('errors::error_page', ['title' => 'General Error'] + get_defined_vars()) ?>

<img class="img-fluid" src="<?= $this->sitebase()->assetUrl('/svg_errors/500.svg');?>" />
<h1 class="jumbotron-heading">500!</h1>
<p class="lead text-muted">Sorry, an error has occurred.</p>