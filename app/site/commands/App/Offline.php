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

use \App\Base\Abstracts\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Psr\Container\ContainerInterface;
use \App\App;

/**
 * Site Offline Command
 */
class Offline extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Set site in Manteinance mode');
    }

    /**
     * {@inheritdocs}
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        @touch(App::getDir(App::APP).DS.'offline.flag');
        $output->writeln('<info>Manteinance mode ON</info>');
    }
}