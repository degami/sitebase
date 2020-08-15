<?php
/**
 * @var $object \App\Base\Abstracts\Models\FrontendModel
 */
$this->layout('frontend::page', ['title' => $object->title] + get_defined_vars()) ?>

<!-- basic page -->