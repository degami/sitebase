<?php

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
    global $app;

    if ($locale == null) {
        $locale = $app->getCurrentLocale();
    }

    return $app->getUtils()->translate($string, params: $params, locale: $locale);
}

/**
 * utility function dump variable
 *
 * @param $variable
 * @param string $level
 */
function k($variable, $level = 'debug')
{
    global $app;

    $app->getContainer()->get('debugbar')['messages']->log($level, $variable);
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
    global $app;

    try {
        $stmt = $app->getContainer()->get('pdo')->prepare($query_string);
        $stmt->execute($params);
        return $stmt;
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }
}
