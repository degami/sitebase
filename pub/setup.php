<?php
ob_start();

function findExecutable($name) {
    $path = trim(shell_exec("command -v $name"));
    return file_exists($path) ? $path : null;
}

$php_bin = findExecutable('php') ?: '/usr/bin/php';
$composer_bin = findExecutable('composer') ?: '/usr/bin/composer';
$composer_dir = dirname($composer_bin);
$npm_bin = findExecutable('npm') ?: '/usr/bin/npm';

chdir(dirname(dirname(__FILE__)));

if (file_exists('.install_done')) {
    die(errorPage('Installation already done.'));
}

if (isset($_REQUEST['info'])) {
  echo phpinfo();
  die();
}

if (isset($_GET['step'])) :
    $dotenv_sections = [
        'Basic Info' => ['APPNAME', 'APPDOMAIN', 'SALT'],
        'Database Info' => ['DATABASE_HOST', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASS'],
        'Admin Info' => ['ADMINPAGES_GROUP', 'ADMIN_USER', 'ADMIN_PASS', 'ADMIN_EMAIL', 'USE2FA_ADMIN'],
        'Cache Info' => ['CACHE_LIFETIME', 'DISABLE_CACHE', 'ENABLE_FPC', 'PRELOAD_REWRITES'],
        'Other Info' => ['DEBUG','GTMID', 'ENABLE_LOGGEDPAGES', 'USE2FA_USERS', 'LOGGEDPAGES_GROUP','GOOGLE_API_KEY','MAPBOX_API_KEY', 'ADMIN_DARK_MODE'],
        'ElasticSearch Info' => ['ELASTICSEARCH','ELASTICSEARCH_HOST','ELASTICSEARCH_PORT'],
        'Smtp Info' => ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS'],
        'SES Info' => ['SES_REGION','SES_PROFILE'],
        'Redis Info' => ['REDIS_CACHE','REDIS_HOST','REDIS_PORT','REDIS_PASSWORD','REDIS_DATABASE'],
        'GraphQL Info' => ['GRAPHQL'],
        'WebHooks Info' => ['WEBHOOKS'],
        'Crud Info' => ['CRUD'],
        'Webdav Info' => ['WEBDAV'],
    ];

    header('Content-Type: application/json');
    if ($_GET['step'] == 1) :
        $check = true;
        $info = "working directory: " . getcwd() . "\n";

        foreach ([$php_bin, $composer_bin, $npm_bin] as $cmd) {
            $check &= file_exists($cmd);
            $info .= $cmd.": " . (file_exists($cmd) ? '' : 'not ') . "found\n";
        }

        echo json_encode([
            'html' => '<pre>'.$info.'</pre>', 
            'js' => ($check == true) ? 'loadStep(2, "Installing PHP dependencies...", false);' : ''
        ]);
    elseif ($_GET['step'] == 2) :
        // install vendors
        putenv("COMPOSER_HOME={$composer_dir}");
        putenv("COMPOSER_ALLOW_XDEBUG=1");
        $command = $php_bin . ' ' . $composer_bin . ' install';

        $output = [];
        echo "{$command}\n";
        exec($command, $output, $return);
        //echo " ({$return})\n";
        if (!empty($output)) {
            echo implode("\n", $output)."\n";
        }

        $html = ob_get_contents()."\n";

        ob_end_clean();
        echo json_encode([
            'html' => '<pre>'.$html.'</pre>', 
            'js' => 'loadStep(3, "Installing Node dependencies...", false);'
        ]);
    elseif ($_GET['step'] == 3) :
        // install vendors
        $command = $npm_bin . ' install';

        $output = [];
        echo "{$command}\n";
        exec($command, $output, $return);
        //echo " ({$return})\n";
        if (!empty($output)) {
            echo implode("\n", $output)."\n";
        }

        $html = ob_get_contents()."\n";

        ob_end_clean();
        echo json_encode([
            'html' => '<pre>'.$html.'</pre>', 
            'js' => 'loadStep(4, "Building files...", false);'
        ]);
    elseif ($_GET['step'] == 4) :
        // build files
        putenv("COMPOSER_HOME={$composer_dir}");
        putenv("COMPOSER_ALLOW_XDEBUG=1");

        $commands = [
            $php_bin . ' ' . $composer_bin .' dump-autoload',
            'bin/console app:deploy',
        ];

        foreach ($commands as $command) {
            $output = [];
            echo "{$command}\n";
            exec($command, $output, $return);
            //echo " ({$return})\n";
            if (!empty($output)) {
                echo implode("\n", $output)."\n";
            }
        }

        $html = ob_get_contents()."\n";

        ob_end_clean();
        echo json_encode([
            'html' => '<pre>'.$html.'</pre><button class="btn btn-primary" id="continuebtn">Continue</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(5, "Fill config data");});'
        ]);
    elseif ($_GET['step'] == 5) :
        // read sample .env file and fill the info
        $dotenv = parse_ini_file('.env.sample');
        if (file_exists('.env')) {
            $dotenv = parse_ini_file('.env');
        }
        $form = '<form action="" id="envform">';

        foreach ($dotenv_sections as $label => $keys) {
            $form .= '<fieldset class="form-group"><legend class="text-left col-form-label pt-0">'.$label.'</legend>';
            foreach ($keys as $key) {
                if (substr($key, 0, 1) == '#' || substr($key, 0, 1) == ';') {
                    continue;
                }
                $value = $dotenv[$key] ?? null;
                $fieldType = $key == 'ADMIN_PASS' ? 'password' : 'textfield';
                $required = in_array($key, ['ADMIN_USER','ADMIN_PASS','ADMIN_EMAIL']);
                $form .= '<div class="form-group row">
                            <div class="col-4"><label for="'.$key.'">'.$key.($required ? ' <em class="required">*</em>' : '').'</label></div>
                            <div class="col-8"><input class="form-control'.($required ? ' required' : '').'" type="'.$fieldType.'" name="'.$key.'" value="'.$value.'" /></div>
                        </div>';
            }
            $form .= '</fieldset>';
        }
        $form .= '<p><em class="required">*</em> Required fields</p>';
        $form .= '</form>';
        echo json_encode([
            'html' => $form.'<button class="btn btn-primary" id="continuebtn">Continue</button>', 
            'js' => implode(" ", array_map('trim', explode("\n", '$(\'#continuebtn\').click(function() { 
                    var form = $(\'#envform\');
                    var valid = true;
                    var missingFields = [];

                    form.find(\'input.required\').each(function() {
                        if (!$(this).val()) {
                            valid = false;
                            missingFields.push($(this).attr(\'name\'));
                            $(this).addClass(\'is-invalid\');
                        } else {
                            $(this).removeClass(\'is-invalid\');
                        }
                    });

                    if (!valid) {
                        alert(\'Please fill in all required fields: \' + missingFields.join(\', \'));
                        return;
                    }

                    var formdata = form.serialize(); 
                    loadStep(6, "Saving data.", true, formdata);});')))
        ]);
    elseif ($_GET['step'] == 6) :
        // save .env file

        $dotenv = '';
        foreach ($dotenv_sections as $label => $keys) {
            $dotenv .= "\n# -- {$label} --\n";
            foreach ($keys as $key) {
                if (in_array($key, ['ADMIN_USER','ADMIN_PASS','ADMIN_EMAIL'])) {
                    continue;
                }
                $value = $_POST[$key] ?? null;
                $dotenv .= "{$key}={$value}\n";
            }
        }
        file_put_contents('.env', trim($dotenv)."\n", LOCK_EX);

        $form = '<form action="" id="envform">';
        foreach (['ADMIN_USER','ADMIN_PASS','ADMIN_EMAIL'] as $key) {
            $value = $_POST[$key];
            $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }
        $form .= '</form>';

        echo json_encode([
            'html' => $form, 
            'js' => 'window.setTimeout(function(){var formdata = $(\'#envform\').serialize(); loadStep(7, "Generate RSA key", false, formdata);}, 1000);'
        ]);

    elseif ($_GET['step'] == 7) :
        // generate rsa key

        $commands = [
            'bin/console generate:rsa_key',
        ];

        foreach ($commands as $command) {
            $output = [];
            echo "{$command}\n";
            exec($command, $output, $return);
            //echo " ({$return})\n";
            if (!empty($output)) {
                echo implode("\n", $output)."\n";
            }
        }

        $html = ob_get_contents()."\n";
        ob_end_clean();

        $form = '<form action="" id="envform">';
        foreach (['ADMIN_USER','ADMIN_PASS','ADMIN_EMAIL'] as $key) {
            $value = $_POST[$key];
            $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }
        $form .= '</form>';

        echo json_encode([
            'html' => $html.$form, 
            'js' => 'window.setTimeout(function(){var formdata = $(\'#envform\').serialize(); loadStep(8, "Run migrations", true, formdata);}, 1000);'
        ]);

    elseif ($_GET['step'] == 8) :
        // run migrations

        $env = [
            'ADMIN_USER' => $_POST['ADMIN_USER'] ?? '',
            'ADMIN_PASS' => $_POST['ADMIN_PASS'] ?? '',
            'ADMIN_EMAIL' => $_POST['ADMIN_EMAIL'] ?? '',
        ];

        foreach($env as $key => $value) {
            putenv("$key=$value");
        }

        $envExport = 'env ';
        foreach ($env as $key => $val) {
            $envExport .= sprintf('%s=%s ', escapeshellarg($key), escapeshellarg($val));
        }

        $commands = [
            $envExport . 'bin/console db:migrate',
        ];

        foreach ($commands as $command) {
            $output = [];
            echo "bin/console db:migrate\n";
            exec($command, $output, $return);
            //echo " ({$return})\n";
            if (!empty($output)) {
                echo implode("\n", $output)."\n";
            }
        }

        $html = ob_get_contents()."\n";
        ob_end_clean();

        echo json_encode([
            'html' => '<pre>'.$html.'</pre><p>Run additional (fake data) migrations?</p><button class="btn btn-primary" id="continuebtn">Continue</button>&nbsp;&nbsp;<button class="btn btn-primary" id="skipbtn">Skip</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(9, "Run additional migrations");});$(\'#skipbtn\').click(function(){loadStep(10, "And that\'s it");});'
        ]);

    elseif ($_GET['step'] == 9) :
        // migrate additionals

        $commands = [
            'bin/console db:migrate_optionals',
        ];

        foreach ($commands as $command) {
            $output = [];
            echo "{$command}\n";
            exec($command, $output, $return);
            //echo " ({$return})\n";
            if (!empty($output)) {
                echo implode("\n", $output)."\n";
            }
        }

        $html = ob_get_contents()."\n";
        ob_end_clean();

        echo json_encode([
            'html' => '<pre>'.$html.'</pre><button class="btn btn-primary" id="continuebtn">Continue</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(10, "And that\'s it");});'
        ]);

    elseif ($_GET['step'] == 10) :
        // TYP

        touch('.install_done');

        $commands = [
            'bin/console c:c',
        ];

        foreach ($commands as $command) {
            $output = [];
            echo "{$command}\n";
            exec($command, $output, $return);
            //echo " ({$return})\n";
            if (!empty($output)) {
                echo implode("\n", $output)."\n";
            }
        }

        $html = ob_get_contents()."\n";
        ob_end_clean();

        echo json_encode([
            'html' => 'Enjoy your site.', 
            'js' => 'window.setTimeout(function(){ document.location = \'/\';}, 5000);'
        ]);
    endif;
else :
    ?><!doctype html>
<html>
<head>
    <title>SiteBase Installation</title>
    <meta charset="utf-8">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Raleway&display=swap" rel="stylesheet">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <style type="text/css">
    body {
        padding: 10px;
        background: #eaeaea;
        color: #636b6f;
        font-family: 'Raleway', sans-serif;
        font-weight: 100;
    }
    .lds-dual-ring {
      display: inline-block;
      width: 64px;
      height: 64px;
    }
    .lds-dual-ring:after {
      content: " ";
      display: block;
      width: 46px;
      height: 46px;
      margin: 1px;
      border-radius: 50%;
      border: 5px solid #666;
      border-color: #666 transparent #666 transparent;
      animation: lds-dual-ring 1.2s linear infinite;
    }
    @keyframes lds-dual-ring {
      0% {
        transform: rotate(0deg);
      }
      100% {
        transform: rotate(360deg);
      }
    }
    em.required {
        color: #f00;
    }
    input.is-invalid {
        border-color: #dc3545;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="row d-flex justify-content-center">
            <h1 class="text-center">Sitebase Installation</h1>

            <div id="main" class="col-10 text-center">
                <h3 id="step-title"></h3>
                <div id="step-content" class="step-content">
                    <div class="row d-flex justify-content-center mt-5">
                        <div class="col-6">
                            <h3>Install your new site</h3>
                            <div class="text-left">
                                Welcome to the sitebase installation program.
                                We will need the "composer" and "npm" programs installed onto your system,
                                and in the $PATH environment.
                                The First two steps in the installation procedure can be quite long, so please be patient.
                                If everything is fine, just click the "start" button.!
                            </div>
                            <div class="text-center mt-5">
                                <button class="btn btn-success" id="btn-start">START</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        (function($){
            function loadStep(numstep, title, clear, formdata) {
                if ($.trim(title) != '') {
                    $('#step-title').html(title);
                }

                if (undefined === clear) {
                    clear = true;
                }

                if (clear) {
                    $('#step-content').html('');
                }

                $('#step-content').append('<div class="lds-dual-ring"></div>');

                $.post('<?= $_SERVER['REQUEST_URI'];?>?step='+numstep, formdata, function(data){
                    $('#step-content').find('.lds-dual-ring').remove();
                    $('#step-content').append(data.html);
                    if($.trim(data.js) != '') {
                        eval(data.js);
                    }
                }, 'json');
            }

            $(document).ready(function(){
                $('#btn-start').click(function(){
                    loadStep(1, 'Start Installation.', true);
                });
            });
        })(jQuery);
    </script>
</body>
</html>
<?php endif;

function errorPage(string $errorMessage) {
?><!doctype html>
<html>
<head>
    <title>SiteBase Installation</title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Raleway&display=swap" rel="stylesheet">
    <style type="text/css">
    body {
        padding: 10px;
        background: #eaeaea;
        color: #636b6f;
        font-family: 'Raleway', sans-serif;
        font-weight: 100;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="row d-flex justify-content-center">
            <h1 class="text-center">Sitebase Installation</h1>

            <div id="main" class="col-10 text-center">
                <h3 id="step-title"></h3>
                <div id="step-content" class="step-content">
                    <div class="row d-flex justify-content-center mt-5">
                        <div class="col-6">
                            <h2><?= $errorMessage ;?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}