<?php

function getMimeType($filename)
{
    $mimeTypes = [
        'css' => 'text/css',
        'js'  => 'application/javascript',
        'jpeg' => 'image/jpg',
        'jpg' => 'image/jpg',
        'png' => 'image/png',
        'map' => 'application/json',
        'json' => 'application/json',
        'woff' => 'application/octect-stream',
        'woff2' => 'application/octect-stream',
        'svg' => 'image/svg+xml',
    ];

    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (isset($mimeTypes[$ext])) {
        return $mimeTypes[$ext];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filename);
    finfo_close($finfo);
    return $mime;
}

chdir(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'pub');
$parts = parse_url($_SERVER["REQUEST_URI"]);
$filePath = realpath(ltrim($parts['path'], '/'));
if ($filePath && is_dir($filePath)) {
    // attempt to find an index file
    foreach (['index.php', 'index.html'] as $indexFile) {
        if ($filePath = realpath($filePath . DIRECTORY_SEPARATOR . $indexFile)) {
            break;
        }
    }
}

if ($filePath && is_file($filePath)) {
    // 1. check that file is not outside of this directory for security
    // 2. check for circular reference to router.php
    // 3. don't serve dotfiles
    if (strpos($filePath, dirname(__DIR__) . DIRECTORY_SEPARATOR) === 0 &&
        $filePath != __FILE__ &&
        substr(basename($filePath), 0, 1) != '.'
    ) {
        if (strtolower(substr($filePath, -4)) == '.php') {
            // php file; serve through interpreter
            include $filePath;
        } else {
            // asset file; serve from filesystem
            header('Content-Type: '.getMimeType($filePath));
            readfile($filePath);
            //return false;
        }
    } else {
        // disallowed file
        header("HTTP/1.1 404 Not Found");
        echo "404 Not Found";
    }
} else {
    // rewrite to our index file
    include 'index.php';
}
