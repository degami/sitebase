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

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\App;
use Symfony\Component\Console\Command\Command;

/**
 * App Deploy Command
 */
class Deploy extends BaseCommand
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
        system("npm install && gulp");

        if ($nestable_js = $this->getUtils()->httpRequest('https://raw.githubusercontent.com/degami/Nestable/master/jquery.nestable.js')) {
            @mkdir(App::getDir(App::WEBROOT) . DS . 'js' . DS . 'jquery-nestable', 0755, true);
            file_put_contents(App::getDir(App::WEBROOT) . DS . 'js' . DS . 'jquery-nestable' . DS . 'jquery.nestable.js', $nestable_js);
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
                App::getDir(App::ASSETS) . DS . 'favicon.ico' => App::getDir('pub') . DS . 'favicon.ico',
            ];
        } else {
            $symlinks = [
                '..' . DS . 'vendor' . DS . 'maximebf' . DS . 'debugbar' . DS . 'src' . DS . 'DebugBar' . DS . 'Resources' => App::getDir('pub') . DS . 'debugbar',
                '..' . DS . 'vendor' . DS . 'components' . DS . 'bootstrap' => App::getDir('pub') . DS . 'bootstrap',
                '..' . DS . 'vendor' . DS . 'components' . DS . 'jqueryui' => App::getDir('pub') . DS . 'jqueryui',
                '..' . DS . 'vendor' . DS . 'components' . DS . 'jquery' => App::getDir('pub') . DS . 'jquery',
                '..' . DS . 'vendor' . DS . 'tinymce' . DS . 'tinymce' => App::getDir('pub') . DS . 'tinymce',
                '..' . DS . 'node_modules' . DS . 'highlightjs' . DS . 'styles' . DS . 'default.css' => App::getDir('pub') . DS . 'css' . DS . 'highlight.css',

                '..' . DS . 'assets' . DS . 'flags' => App::getDir('pub') . DS . 'flags',
                '..' . DS . 'assets' . DS . 'sitebase_logo.png' => App::getDir('pub') . DS . 'sitebase_logo.png',
                '..' . DS . 'assets' . DS . 'favicon.ico' => App::getDir('pub') . DS . 'favicon.ico',
            ];
        }

        foreach ($symlinks as $from => $to) {
            if (!file_exists($to) && file_exists($from)) {
                echo "symlink " . $from . " to " . $to . "\n";
                symlink($from, $to);
            }
        }

        return Command::SUCCESS;
    }
}
