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
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \App\Site\Models\Website;
use \App\App;

/**
 * Http Server Command
 * @package App\Site\Commands\App
 */
class Serve extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Run PHP Http Server')
            ->setDefinition(
                new InputDefinition(
                    [
                    new InputOption('port', 'p', InputOption::VALUE_OPTIONAL),
                    //new InputOption('website', 'w', InputOption::VALUE_OPTIONAL),
                    ]
                )
            );
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
        $port = $input->getOption('port');
        if (!is_numeric($port) || $port < 1024) {
            $port = 8000;
        }

        $website_id = $input->getOption('website');
        $website = null;
        if (!is_numeric($website_id) || ($website = $this->getContainer()->call([Website::class,'load'], ['id' => $website_id]))->id != $website_id) {
            $website = null;
            $website_id = 1;
        }

        if (!$website instanceof Website) {
            $website = $this->getContainer()->call([Website::class,'load'], ['id' => $website_id]);
        }

        echo "Serving [".$website->domain."] pages on http://localhost:".$port."\n";
        system("website_id=".$website_id." php -S localhost:".$port." ".App::getDir('root').DS.'php_server'.DS.'router.php');
    }
}
