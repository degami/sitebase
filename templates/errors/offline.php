<?php $this->layout('errors::error_page', ['title' => 'Server Maintenance'] + get_defined_vars()) ?>

<img class="img-fluid" src="<?= $this->sitebase()->assetUrl('/svg_errors/offline.svg');?>" />
<h1 class="jumbotron-heading">Site Offline!</h1>
<p class="lead text-muted">Under Maintenance. please try again later</p>