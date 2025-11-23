<?php

use App\App;

/**
 * translate string
 *
 * @param  string $string
 * @param array $params
 * @param  string|null $locale
 * @return string
 */
function __(string $string, array $params = [], ?string $locale = null)
{
    if ($locale == null) {
        $locale = App::getInstance()->getCurrentLocale();
    }

    return App::getInstance()->getUtils()->translate($string, params: $params, locale: $locale);
}

/**
 * utility function dump variable
 *
 * @param $variable
 * @param string $level
 */
function k($variable, $level = 'debug')
{
    App::getInstance()->getContainer()->get('debugbar')['messages']->log($level, $variable);
}

/**
 * utility function to quickly execute a statement
 *
 * @param $query_string
 * @param null $params
 * @return bool|PDOStatement
 */
function dbq($query_string, mixed $params = null)
{
    try {
        $stmt = App::getInstance()->getPdo()->prepare($query_string);
        $stmt->execute($params);
        return $stmt;
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }
}

function isJson($content)
{
    if (!is_string($content)) {
        return false;
    }

    $decoded = json_decode($content);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return is_array($decoded) || is_object($decoded);
}

function array_unique_by(array $items, callable $callback): array {
    $seen = [];
    $result = [];

    foreach ($items as $item) {
        $key = $callback($item);

        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $result[] = $item;
        }
    }

    return $result;
}