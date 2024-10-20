<?php

use App\Base\Abstracts\Controllers\FrontendPage;

function theme_alterTemplateName(string $templateName, FrontendPage $controllerObject) : string
{
    if (file_exists(__DIR__ . DS . $templateName . '.php')) {
        return $templateName;
    }

    if (in_array($templateName, ['homepage', 'event_detail', 'event_list', 'news_detail', 'news_list', 'page', 'taxonomy'])) {
        return 'layout';
    }

    return $templateName;
}