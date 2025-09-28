<?php
/**
 * @var $object \App\Base\Abstracts\Models\FrontendModel
 */
$this->layout('frontend::layout', ['title' => $object->title] + get_defined_vars()) ?>

<!-- basic page -->