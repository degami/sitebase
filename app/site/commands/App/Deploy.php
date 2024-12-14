<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Commands\App;

use Degami\Basics\Exceptions\BasicException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\App;
use App\Base\Abstracts\Commands\BaseExecCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * App Deploy Command
 */
class Deploy extends BaseExecCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Run Deploy Tasks')
            ->addOption('absolute_symlink', null, InputOption::VALUE_OPTIONAL, 'Use absolute symlinks', false);
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if (App::getInstance()->getEnv('SALT') == "") {
            $this->getIo()->info("Missing SALT in .env, adding a random value");
            $application = $this->getApplication();

            if ($application === null) {
                $output->writeln('<error>Errors loading Application!</error>');
                return Command::FAILURE;
            }
    
            // Recupera il secondo comando
            $command = $application->find('app:update_salt');
    
            // Crea l'input per il secondo comando
            $arguments = [
                'command' => 'app:update_salt',
            ];
            $arrayInput = new ArrayInput($arguments);
    
            // Esegui il secondo comando
            $returnCode = $command->run($arrayInput, $output);
    
            if ($returnCode !== Command::SUCCESS) {
                $output->writeln('<error>Errors executing updateSalt command!</error>');
                return Command::FAILURE;
            }
        }

        $this->executeCommand("npm install && gulp 2> /dev/null");

        if ($nestable_js = $this->getUtils()->httpRequest('https://raw.githubusercontent.com/degami/Nestable/master/jquery.nestable.js')) {
            @mkdir(App::getDir(App::WEBROOT) . DS . 'js' . DS . 'jquery-nestable', 0755, true);
            file_put_contents(App::getDir(App::WEBROOT) . DS . 'js' . DS . 'jquery-nestable' . DS . 'jquery.nestable.js', $nestable_js);
        }

        if (!is_dir(App::getDir(App::ASSETS) . DS . 'minipaint') || empty(glob(App::getDir(App::ASSETS) . DS . 'minipaint/*'))) {
            if ($minipaint_zip = $this->getUtils()->httpRequest('https://github.com/viliusle/miniPaint/archive/refs/heads/master.zip')) {
                @mkdir(App::getDir(App::ASSETS) . DS . 'minipaint', 0755, true);
                file_put_contents(App::getDir(App::ASSETS) . DS . 'minipaint' . DS . 'minipaint.zip', $minipaint_zip);
                $oldCWD = getcwd();
                chdir(App::getDir(App::ASSETS) . DS . 'minipaint');
                $this->executeCommand('unzip minipaint.zip');
                
                $fd = opendir("miniPaint-master");
                while ($dirent = readdir($fd)) {
                    if ($dirent == '.' || $dirent == '..') {
                        continue;
                    }
                    rename("miniPaint-master" . DS . $dirent, basename($dirent));
                }
                closedir($fd);
    
                $this->delTree('miniPaint-master');
                $this->delTree('examples');
                $this->delTree('src');
                $this->delTree('tools');

                @unlink(App::getDir(App::ASSETS) . DS . 'minipaint' . DS . '.babelrc');
                @unlink(App::getDir(App::ASSETS) . DS . 'minipaint' . DS . '.gitignore');
                @unlink(App::getDir(App::ASSETS) . DS . 'minipaint' . DS . 'package-lock.json');
                @unlink(App::getDir(App::ASSETS) . DS . 'minipaint' . DS . 'package.json');
                @unlink(App::getDir(App::ASSETS) . DS . 'minipaint' . DS . 'webpack.config.js');

                chdir($oldCWD);
                @unlink(App::getDir(App::ASSETS) . DS . 'minipaint' . DS . 'minipaint.zip');
            }    
        }
        
        $absolute_symlinks = $input->getOption('absolute_symlink') ?? false;

        if ($absolute_symlinks) {
            $symlinks = [
                App::getDir(App::ROOT) . DS . 'vendor' . DS . 'maximebf' . DS . 'debugbar' . DS . 'src' . DS . 'DebugBar' . DS . 'Resources' => App::getDir('pub') . DS . 'debugbar',
                App::getDir(App::ROOT) . DS . 'vendor' . DS . 'components' . DS . 'bootstrap' => App::getDir('pub') . DS . 'bootstrap',
                App::getDir(App::ROOT) . DS . 'vendor' . DS . 'components' . DS . 'jqueryui' => App::getDir('pub') . DS . 'jqueryui',
                App::getDir(App::ROOT) . DS . 'vendor' . DS . 'components' . DS . 'jquery' => App::getDir('pub') . DS . 'jquery',
                App::getDir(App::ROOT) . DS . 'vendor' . DS . 'tinymce' . DS . 'tinymce' => App::getDir('pub') . DS . 'tinymce',
                App::getDir(App::ROOT) . DS . 'node_modules' . DS . 'highlightjs' . DS . 'styles' . DS . 'default.css' => App::getDir('pub') . DS . 'css' . DS . 'highlight.css',

                App::getDir(App::FLAGS) => App::getDir('pub') . DS . 'flags',
                App::getDir(App::ASSETS) . DS . 'sitebase_logo.png' => App::getDir('pub') . DS . 'sitebase_logo.png',
                App::getDir(App::ASSETS) . DS . 'sitebase_logo_small.png' => App::getDir('pub') . DS . 'sitebase_logo_small.png',
                App::getDir(App::ASSETS) . DS . 'favicon.ico' => App::getDir('pub') . DS . 'favicon.ico',
            ];
        } else {
            $symlinks = [
                '..' . DS . 'vendor' . DS . 'maximebf' . DS . 'debugbar' . DS . 'src' . DS . 'DebugBar' . DS . 'Resources' => App::getDir('pub') . DS . 'debugbar',
                '..' . DS . 'vendor' . DS . 'components' . DS . 'bootstrap' => App::getDir('pub') . DS . 'bootstrap',
                '..' . DS . 'vendor' . DS . 'components' . DS . 'jqueryui' => App::getDir('pub') . DS . 'jqueryui',
                '..' . DS . 'vendor' . DS . 'components' . DS . 'jquery' => App::getDir('pub') . DS . 'jquery',
                '..' . DS . 'vendor' . DS . 'tinymce' . DS . 'tinymce' => App::getDir('pub') . DS . 'tinymce',
                '..' . DS . '..' . DS . 'node_modules' . DS . 'highlightjs' . DS . 'styles' . DS . 'default.css' => App::getDir('pub') . DS . 'css' . DS . 'highlight.css',

                '..' . DS . 'assets' . DS . 'flags' => App::getDir('pub') . DS . 'flags',
                '..' . DS . 'assets' . DS . 'sitebase_logo.png' => App::getDir('pub') . DS . 'sitebase_logo.png',
                '..' . DS . 'assets' . DS . 'sitebase_logo_small.png' => App::getDir('pub') . DS . 'sitebase_logo_small.png',
                '..' . DS . 'assets' . DS . 'favicon.ico' => App::getDir('pub') . DS . 'favicon.ico',
            ];
        }

        chdir(App::getDir(App::WEBROOT));
        foreach ($symlinks as $from => $to) {
            $to_check = file_exists($to);
            $from_check = file_exists($from);
            if (!$absolute_symlinks && !$from_check) {
                $oldCWD = getcwd();

                chdir(dirname($to));
                $from_check = file_exists($from);

                chdir($oldCWD);
            }
            
            if (!$to_check && $from_check) {
                $output->writeln("symlink <info>$from</info> to <info>$to</info>");
                symlink($from, $to);
            }
        }

        return Command::SUCCESS;
    }

    protected function delTree(string $path) : bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (is_dir($path)) {
            if (($dir = opendir($path)) !== false) {
                while ($dirent = readdir($dir)) {
                    if ($dirent == '.' || $dirent == '..') {
                        continue;
                    }

                    $this->delTree($path . DS . $dirent);
                }
                closedir($dir);
            }
            @rmdir($path);
        } else {
            @unlink($path);
        }

        return true;
    }
}
