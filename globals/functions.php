<?php

/**
 * translate string
 * @param  string $string
 * @param  string $locale
 * @return string
 */
function __($string, $locale = null)
{
    global $app;

    if ($locale == null) {
        $locale = $app->getCurrentLocale();
    }

    return $app->getUtils()->translate($string, $locale);
}
