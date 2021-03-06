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

use App\Base\Abstracts\Commands\BaseExecCommand;
use App\Base\Exceptions\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use App\App;

/**
 * Generate RSA Key Command
 */
class RsaKey extends BaseExecCommand
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

//        $commandline = "openssl genrsa -out " . App::getDir(App::ASSETS) . DS . "rsa_private.key 2048";

        $commandline = "openssl req -new -newkey -sha256 -nodes -subj \"/C=IT/ST=IT/L=City/O=Organization/CN=CommonName\" -keyout " . App::getDir(App::ASSETS) . DS . "rsa_private.key -out " . App::getDir(App::ASSETS) . DS . "rsa_private.csr";
        echo $commandline."\n";
        system($commandline);

        $commandline = "openssl x509 -req -sha256 -days 365 -in " . App::getDir(App::ASSETS) . DS . "rsa_private.csr -signkey " . App::getDir(App::ASSETS) . DS . "rsa_private.key -out " . App::getDir(App::ASSETS) . DS . "rsa_private.pem";
        system($commandline);

        $output->writeln("<info>Key created</info>");
    }
}
