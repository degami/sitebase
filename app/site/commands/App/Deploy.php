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
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\Table;
use \Psr\Container\ContainerInterface;
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
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        system("npm install && gulp");

        $symlinks = [
            App::getDir('root').DS.'vendor'.DS.'maximebf'.DS.'debugbar'.DS.'src'.DS.'DebugBar'.DS.'Resources' => App::getDir('pub').DS.'debugbar',
            App::getDir('root').DS.'vendor'.DS.'components'.DS.'bootstrap' => App::getDir('pub').DS.'bootstrap',
            App::getDir('root').DS.'vendor'.DS.'components'.DS.'jqueryui' => App::getDir('pub').DS.'jqueryui',
            App::getDir('root').DS.'vendor'.DS.'components'.DS.'jquery' => App::getDir('pub').DS.'jquery',
            App::getDir('root').DS.'vendor'.DS.'tinymce'.DS.'tinymce' => App::getDir('pub').DS.'tinymce',
            App::getDir('flags') => App::getDir('pub').DS.'flags',
            App::getDir('assets').DS.'sitebase_logo.png' => App::getDir('pub').DS.'sitebase_logo.png',
            App::getDir('assets').DS.'favicon.ico' => App::getDir('pub').DS.'favicon.ico',
        ];

        foreach ($symlinks as $from => $to) {
            if (!file_exists($to)) {
                echo "symlink ".$from." to ".$to."\n";
                symlink($from, $to);
            }
        }
    }
}
