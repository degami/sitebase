<?php
/**
 * @var \App\Base\Abstracts\Models\FrontendModel $object
 */
$this->layout('frontend::layout', ['title' => $object->title] + get_defined_vars()) ?>

<!-- basic page -->