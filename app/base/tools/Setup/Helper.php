<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Tools\Setup;

/**
 * Setup Helper
 * 
 * this class tries to be as "php vanilla" as possible as it is used by the Setup router and the pub/setup.php file
 */
class Helper {

    protected ?string $php_bin = null;
    protected ?string $composer_bin = null;
    protected ?string $composer_dir = null;
    protected ?string $npm_bin = null;
    protected ?string $console_bin = null;


    protected function findExecutable($name) : ?string
    {
        $path = trim(shell_exec("command -v $name"));
        return file_exists($path) ? $path : null;
    }

    protected function getPhpBin() : ?string
    {
        if (is_null(($this->php_bin))) {
            $this->php_bin = $this->findExecutable('php') ?: '/usr/bin/php';
        }

        return $this->php_bin;
    }

    protected function getComposerBin() : ?string
    {
        if (is_null(($this->composer_bin))) {
            $this->composer_bin = $this->findExecutable('composer') ?: '/usr/bin/composer';
        }

        return $this->composer_bin;
    }

    protected function getComposerDir() : string
    {
        if (is_null(($this->composer_dir))) {
            $this->composer_dir = dirname($this->getComposerBin());
        }

        return $this->composer_dir;
    }

    protected function getNpmBin() : string
    {
        if (is_null(($this->npm_bin))) {
            $this->npm_bin = $this->findExecutable('npm') ?: '/usr/bin/npm';
        }

        return $this->npm_bin;
    }

    protected function getConsoleBin() : string
    {
        if (is_null(($this->console_bin))) {
            $this->console_bin = getcwd() . '/bin/console';
        }

        return $this->console_bin;
    }

    protected function getDotenvSections() : array
    {
        return [
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
    }

    public function checkRequirements() : array
    {
        $check = true;
        $info = "working directory: " . getcwd() . "\n";

        $php_bin = $this->getPhpBin();
        $composer_bin = $this->getComposerBin();
        $npm_bin = $this->getNpmBin();        

        foreach ([$php_bin, $composer_bin, $npm_bin] as $cmd) {
            $check &= file_exists($cmd);
            $info .= $cmd.": " . (file_exists($cmd) ? '' : 'not ') . "found\n";
        }

        // create an empty .env if it does not exists
        touch('.env');

        return [$check, $info];
    }

    public function step1() : array
    {
        [$check, $info] = $this->checkRequirements();

        return [
            'html' => '<pre>'.$info.'</pre>', 
            'js' => ($check == true) ? 'loadStep(2, "Installing PHP dependencies...", false);' : ''
        ];

        return $this->checkRequirements();
    }

    public function composerInstall() : string
    {
        ob_start();

        $php_bin = $this->getPhpBin();
        $composer_bin = $this->getComposerBin();
        $composer_dir = $this->getComposerDir();

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

        $consoleOutput = ob_get_contents()."\n";

        ob_end_clean();
        
        return $consoleOutput;
    }

    public function step2() : array
    {
        $html = $this->composerInstall();

        return [
            'html' => '<pre>'.$html.'</pre>', 
            'js' => 'loadStep(3, "Installing Node dependencies...", false);'
        ];
    }

    public function npmInstall() : string
    {
        // npm install is executed also by the app:deploy cli command - executing before to "skip" the download time after
        ob_start();
        $npm_bin = $this->getNpmBin();        

        // install vendors
        $command = $npm_bin . ' install';

        $output = [];
        echo "{$command}\n";
        exec($command, $output, $return);
        //echo " ({$return})\n";
        if (!empty($output)) {
            echo implode("\n", $output)."\n";
        }

        $consoleOutput = ob_get_contents()."\n";

        ob_end_clean();
        
        return $consoleOutput;
    }

    public function step3() : array
    {
        $html = $this->npmInstall();

        return [
            'html' => '<pre>'.$html.'</pre>', 
            'js' => 'loadStep(4, "Building files...", false);'
        ];
    }

    public function dumpAutoloadAndAppDeploy() : string
    {
        ob_start();
        $php_bin = $this->getPhpBin();
        $composer_bin = $this->getComposerBin();
        $composer_dir = $this->getComposerDir();

        // build files
        putenv("COMPOSER_HOME={$composer_dir}");
        putenv("COMPOSER_ALLOW_XDEBUG=1");

        $commands = [
            $php_bin . ' ' . $composer_bin .' dump-autoload',
            $php_bin . ' bin/console app:deploy',
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

        $consoleOutput = ob_get_contents()."\n";

        ob_end_clean();
        
        return $consoleOutput;
    }

    public function step4() : array
    {
        $html = $this->dumpAutoloadAndAppDeploy();

        return [
            'html' => '<pre>'.$html.'</pre><button class="btn btn-primary" id="continuebtn">Continue</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(5, "Fill config data");});'
        ];
    }

    public function dotEnvForm() : string
    {
        $dotenv_sections = $this->getDotenvSections();

        // read sample .env file and fill the info
        $dotenv = parse_ini_file('.env.sample');
        if (file_exists('.env') && !empty(file_get_contents('.env'))) {
            // read .env if it has informations
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
        
        return $form;
    }

    public function step5() : array
    {
        $form = $this->dotEnvForm();

        return [
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
        ];
    }

    public function saveDotEnv() : string
    {
        $dotenv_sections = $this->getDotenvSections();

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

        return $form;
    }

    public function step6() : array
    {
        $form = $this->saveDotEnv();

        return [
            'html' => $form, 
            'js' => 'window.setTimeout(function(){var formdata = $(\'#envform\').serialize(); loadStep(7, "Generate RSA key", false, formdata);}, 1000);'
        ];
    }

    public function generateRsa() : string
    {
        ob_start();
        $php_bin = $this->getPhpBin();

        // generate rsa key
        $commands = [
            $php_bin . ' bin/console generate:rsa_key',
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

        $consoleOutput = ob_get_contents()."\n";
        ob_end_clean();

        return $consoleOutput;
    }

    public function step7() : array
    {
        $html = $this->generateRsa();

        $form = '<form action="" id="envform">';
        foreach (['ADMIN_USER','ADMIN_PASS','ADMIN_EMAIL'] as $key) {
            $value = $_POST[$key];
            $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }
        $form .= '</form>';

        return [
            'html' => $html.$form, 
            'js' => 'window.setTimeout(function(){var formdata = $(\'#envform\').serialize(); loadStep(8, "Run migrations", true, formdata);}, 1000);'
        ];
    }

    public function execMigrations() : string
    {
        ob_start();
        $php_bin = $this->getPhpBin();

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
            $envExport . $php_bin . ' bin/console db:migrate',
        ];

        foreach ($commands as $command) {
            $output = [];
            echo $php_bin . " bin/console db:migrate\n";
            exec($command, $output, $return);
            //echo " ({$return})\n";
            if (!empty($output)) {
                echo implode("\n", $output)."\n";
            }
        }

        $consoleOutput = ob_get_contents()."\n";
        ob_end_clean();

        return $consoleOutput;
    }

    public function step8() : array
    {
        $html = $this->execMigrations();

        return [
            'html' => '<pre>'.$html.'</pre><p>Run additional (fake data) migrations?</p><button class="btn btn-primary" id="continuebtn">Continue</button>&nbsp;&nbsp;<button class="btn btn-primary" id="skipbtn">Skip</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(9, "Run additional migrations");});$(\'#skipbtn\').click(function(){loadStep(10, "And that\'s it");});'
        ];
    }

    public function execOptionalMigrations() : string
    {
        ob_start();
        $php_bin = $this->getPhpBin();

        // migrate additionals
        $commands = [
            $php_bin . ' bin/console db:migrate_optionals',
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

        $consoleOutput = ob_get_contents()."\n";
        ob_end_clean();

        return $consoleOutput;
    }

    public function step9() : array
    {
        $html = $this->execOptionalMigrations();

        return [
            'html' => '<pre>'.$html.'</pre><button class="btn btn-primary" id="continuebtn">Continue</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(10, "Install crontabs");});'
        ];
    }

    public function crontabStep() : array
    {
        return [
            'html' => '<p>Install crontabs?</p><button class="btn btn-primary" id="continuebtn">Continue</button>&nbsp;&nbsp;<button class="btn btn-primary" id="skipbtn">Skip</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(11, "Install crontabs");});$(\'#skipbtn\').click(function(){loadStep(12, "And that\'s it");});'
        ];
    }

    public function step10() : array 
    {
        return $this->crontabStep();    
    }

    protected function addCronToCrontab($crontabCommand) {
        // Get current crontab
        $currentCrontab = shell_exec('crontab -l 2>/dev/null');
        $cronLines = explode("\n", (string) $currentCrontab);
        
        // check if command is already installed
        $found = false;
        foreach ($cronLines as $line) {
            if (strpos($line, $crontabCommand) !== false) {
                $found = true;
                break;
            }
        }
    
        if (!$found) {
            $cronLines[] = "* * * * * $crontabCommand > /dev/null 2>&1";
            $newCrontab = implode("\n", $cronLines);
            
            // Scrivi il nuovo crontab temporaneamente su file
            $tmpFile = tempnam(sys_get_temp_dir(), 'cron');
            file_put_contents($tmpFile, $newCrontab);
            shell_exec("crontab $tmpFile");
            unlink($tmpFile);

            return "Adding '* * * * * $crontabCommand > /dev/null 2>&1'\n";
        }

        return "$crontabCommand is already installed\n";
    }

    public function installCrontabs() : string
    {
        $php_bin = $this->getPhpBin();
        $console_bin = $this->getConsoleBin();

        return $this->addCronToCrontab("$php_bin $console_bin cron:run") . $this->addCronToCrontab("$php_bin $console_bin queue:process");
    }

    public function step11() : array
    {
        $html = $this->installCrontabs();

        return [
            'html' => '<pre>'.$html.'</pre><button class="btn btn-primary" id="continuebtn">Continue</button>', 
            'js' => '$(\'#continuebtn\').click(function(){loadStep(12, "And that\'s it");});'
        ];
    }

    public function setInstallDone() : string
    {
        ob_start();
        $php_bin = $this->getPhpBin();

        // TYP
        touch('.install_done');

        $commands = [
            $php_bin . ' bin/console c:c',
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

        $consoleOutput = ob_get_contents()."\n";
        ob_end_clean();

        return $consoleOutput;
    }

    public function step12() : array
    {
        $html = $this->setInstallDone();

        return [
            'html' => 'Enjoy your site.', 
            'js' => 'window.setTimeout(function(){ document.location = \'/\';}, 5000);'
        ];
    }

    public function errorPage(string $errorMessage) : string
    {
        return <<<HTML
<!doctype html>
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
                            <h2>{$errorMessage}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public function step0(): string
    {
        return <<<HTML
<!doctype html>
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
                                and in the \$PATH environment.
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

                $.post('{$_SERVER['REQUEST_URI']}?step='+numstep, formdata, function(data){
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
HTML;
    }
}