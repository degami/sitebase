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
namespace App\Site\Commands\Generate;

use \App\Base\Abstracts\Commands\BaseCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use \App\App;

/**
 * Generate Documentation Command
 */
class Docs extends BaseCommand
{
    const BASE_NAMESPACE = "App\\Site\\Cron\\Tasks\\";

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate documentation with phpdoc');
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
        $helper = $this->getHelper('question');
        $output->writeln("<info>Generating Documentation</info>");
        system("phpdoc -t ".App::getDir(App::ROOT).DS."docs -d ".App::getDir(App::APP)." >/dev/null 2>&1");

        if (!file_exists(App::getDir(App::WEBROOT).DS."docs")) {
            $question = new ConfirmationQuestion('Do you want to publish docs also on website? ', false);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<info>Not Publishing</info>');
                return;
            }

            symlink(App::getDir(App::ROOT).DS."docs", App::getDir(App::WEBROOT).DS."docs");
        }

        $output->writeln("<info>Task completed</info>");
    }
}
