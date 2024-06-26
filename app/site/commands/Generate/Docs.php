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

namespace App\Site\Commands\Generate;

use App\Base\Abstracts\Commands\BaseExecCommand;
use App\Base\Exceptions\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use App\App;

/**
 * Generate Documentation Command
 */
class Docs extends BaseExecCommand
{
    public const BASE_NAMESPACE = "App\\Site\\Cron\\Tasks\\";

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Generating Documentation</info>");

        if (!$this->commandExist('phpdoc')) {
            throw new NotFoundException('phpdoc command is missing!');
        }

        $commandline = "phpdoc -t " . App::getDir(App::ROOT) . DS . "docs -d " . App::getDir(App::APP) . " --sourcecode --ignore=vendor/* --template=clean --setting=\"graphs.enabled=true\" >/dev/null 2>&1";
        system($commandline);

        if (!file_exists(App::getDir(App::WEBROOT) . DS . "docs")) {
            $question = new ConfirmationQuestion('Do you want to publish docs also on website? ', false);
            if (!$this->getQuestionHelper()->ask($input, $output, $question)) {
                $output->writeln('<info>Not Publishing</info>');
                return;
            }

            symlink(App::getDir(App::ROOT) . DS . "docs", App::getDir(App::WEBROOT) . DS . "docs");
        }

        $this->getIo()->success('Task completed');
    }
}
