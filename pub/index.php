<?php
if (!file_exists('../.env') || !is_dir('../vendor')) {
    header('Location: /setup.php');
    exit();
}

require '../config/defines.php';
require '../vendor/autoload.php';

$app = new App\App();
$app->bootstrap();
