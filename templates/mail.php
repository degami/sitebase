<?php
/**
 * @var string $subject
 */

$lang = $lang ?? $this->sitebase()->getCurrentLocale();
$title = $subject ?? $this->sitebase()->getCurrentWebsiteName();
$this->layout('base::html', get_defined_vars()) ?>

<?= $this->section('content'); ?>
