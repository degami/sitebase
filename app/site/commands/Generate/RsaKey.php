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

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Exceptions\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use App\App;

/**
 * Generate RSA Key Command
 */
class RsaKey extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate RSA key');
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
        $output->writeln("<info>Generating RSA key</info>");

        if (!$this->commandExist('openssl')) {
            throw new NotFoundException('openssl command is missing!');
        }

        $commandline = "openssl genrsa -out " . App::getDir(App::ASSETS) . DS . "rsa_private.key 2048";

        system($commandline);

        $output->writeln("<info>Key created</info>");
    }

    /**
     * Checks if command exists
     *
     * @param $cmd
     *
     * @return bool
     */
    protected function commandExist($cmd): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }
}
