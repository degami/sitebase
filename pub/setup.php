<?php
ob_start();

require '../app/base/tools/Setup/Helper.php';

use App\Base\Tools\Setup\Helper as SetupHelper;

chdir(dirname(dirname(__FILE__)));

$setupHelper = new SetupHelper();

if (file_exists('.install_done')) {
    http_response_code(404);
    die($setupHelper->errorPage('Installation already done.'));
} else {
    if (is_file('vendor/autoload.php') && !isset($_GET['step'])) { // if vendors are installed  and we are not already executing an installation we can continue on setup_router
        header('Location: /setup/');
        exit();
    }
}

if (isset($_REQUEST['info'])) {
  echo phpinfo();
  die();
}

if (isset($_GET['step'])) :
    switch ($_GET['step']) {
        case 0:
            echo $setupHelper->step0();
            break;
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
        case 6:
        case 7:
        case 8:
        case 9:
        case 10:
        case 11:
        case 12:
            header('Content-Type: application/json');
            echo json_encode($setupHelper->{'step'.$_GET['step']}());
            break;
        default:
            echo $setupHelper->errorPage('Invalid Step!');
            break;
    }
else :
    echo $setupHelper->step0();
endif;