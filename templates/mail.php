<?php
/**
 * @var $subject string
 */

$lang = $lang ?? $this->sitebase()->getCurrentLocale();
$title = $subject ?? $this->sitebase()->getCurrentWebsiteName();
$this->layout('base::html', get_defined_vars()) ?>

<?= $this->section('content'); ?>
