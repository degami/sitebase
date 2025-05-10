<?php

// we need to keep a "vendor free" installer
if (!file_exists('../.env') || !is_dir('../vendor')) {
    header('Location: /setup.php');
    exit();
}

require '../config/defines.php';
require '../vendor/autoload.php';

use App\App;

if (!App::installDone() && !str_starts_with($_SERVER['REQUEST_URI'], '/setup/')) {
    header('Location: /setup/');
    exit();
}

$app = new App();
$app->bootstrap();
