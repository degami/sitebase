<?php
/**
 * @var string $subject
 * @var string $orderNumber
 * @var string $comment
 */

$this->layout('mails::layout', get_defined_vars()) ?>


<p><?= $this->sitebase()->translate('A new comment has been added to your order %s', [$orderNumber]); ?></p>
<p><em><?= $comment; ?></em></p>