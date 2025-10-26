<?php $this->layout('errors::error_page', ['title' => 'Not Found!'] + get_defined_vars()) ?>

<img class="img-fluid" src="<?= $this->sitebase()->assetUrl('/svg_errors/404.svg');?>" />
<h1 class="jumbotron-heading">404!</h1>
<p class="lead text-muted">Sorry, the page you requested was not found.</p>
