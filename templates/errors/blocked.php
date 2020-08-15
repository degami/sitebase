<?php
/**
 * @var $ip_addr string
 */
$this->layout('errors::error_page', ['title' => 'Blocked IP'] + get_defined_vars()) ?>

<h1 class="jumbotron-heading">Blocked IP!</h1>
<p class="lead text-muted">Sorry, your IP <strong><?= $ip_addr;?></strong> has been blocked. Please Contact the site administrators.</p>
