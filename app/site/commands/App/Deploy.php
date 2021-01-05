<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Commands\App;

use \App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use GuzzleHttp\Exception\GuzzleException;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \App\App;

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
        $this->setDescription('Run Deploy Tasks');
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        system("npm install && gulp");

        if ($nestable_js = $this->getUtils()->httpRequest('https://raw.githubusercontent.com/degami/Nestable/master/jquery.nestable.js')) {
            @mkdir(App::getDir(App::WEBROOT) . DS . 'js' . DS . 'jquery-nestable', 0755, true);
            file_put_contents(App::getDir(App::WEBROOT) . DS . 'js' . DS . 'jquery-nestable' . DS . 'jquery.nestable.js', $nestable_js);
        }

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

        foreach ($symlinks as $from => $to) {
            if (!file_exists($to)) {
                echo "symlink " . $from . " to " . $to . "\n";
                symlink($from, $to);
            }
        }
    }
}
