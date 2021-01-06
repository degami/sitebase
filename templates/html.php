<?php
/**
 * @var $title string
 * @var $lang string
 * @var $body_class string
 */

?><!doctype html>
<html<?php if (!empty($lang)):?> lang="<?= $lang; ?>"<?php endif;?>>
<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>
    <?= $this->section('head'); ?>
</head>
<body<?php if (!empty($body_class)):?> class="<?= $body_class;?>"<?php endif;?>>
<?= $this->section('content'); ?>
</body>
</html>