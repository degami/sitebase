<?php

namespace Degami\PHPFormsApi;

define('FORMS_DEFAULT_FORM_CONTAINER_TAG', 'div');
define('FORMS_DEFAULT_FORM_CONTAINER_CLASS', 'form-container');
define('FORMS_DEFAULT_FIELD_CONTAINER_TAG', 'div');
define('FORMS_DEFAULT_FIELD_CONTAINER_CLASS', 'form-item');
define('FORMS_DEFAULT_FIELD_LABEL_CLASS', '');
define('FORMS_FIELD_ADDITIONAL_CLASS', 'form-control');
define(
    'FORMS_ERRORS_TEMPLATE',
    '<div class="alert alert-danger"><ul>%s</ul></div>'
);
define(
    'FORMS_HIGHLIGHTS_TEMPLATE',
    '<div class="alert alert-info"><ul>%s</ul></div>'
);
define(
    'DEFAULT_TINYMCE_OPTIONS',
    [
    'menubar' => false,
    'plugins' => "code,link,lists,preview,searchreplace,media,table",
    'relative_urls' => false,
    'remove_script_host' => true,
    'document_base_url' => "",
    'content_style' => "body { font-family:Helvetica,Arial,sans-serif; font-size:16px }",
    ]
);
