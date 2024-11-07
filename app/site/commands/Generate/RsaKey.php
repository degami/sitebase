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
use App\App;
use Symfony\Component\Console\Command\Command;

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
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->renderTitle('Generating RSA key');
/*
        if (!$this->commandExist('openssl')) {
            throw new NotFoundException('openssl command is missing!');
        }

//        $commandline = "openssl genrsa -out " . App::getDir(App::ASSETS) . DS . "rsa_private.key 2048";

        $commandline = "openssl req -new -newkey -sha256 -nodes -subj \"/C=IT/ST=IT/L=City/O=Organization/CN=CommonName\" -keyout " . App::getDir(App::ASSETS) . DS . "rsa_private.key -out " . App::getDir(App::ASSETS) . DS . "rsa_private.csr";
        echo $commandline."\n";
        $this->executeCommand($commandline);

        $commandline = "openssl x509 -req -sha256 -days 365 -in " . App::getDir(App::ASSETS) . DS . "rsa_private.csr -signkey " . App::getDir(App::ASSETS) . DS . "rsa_private.key -out " . App::getDir(App::ASSETS) . DS . "rsa_private.pem";
        $this->executeCommand($commandline);
*/

        $this->generateCSR('IT', 'IT', 'City', 'Organization', 'CommonName');
        $this->generateCertificate(
            App::getDir(App::ASSETS) . DS . 'rsa_private.csr',
            App::getDir(App::ASSETS) . DS . 'rsa_private.key',
            App::getDir(App::ASSETS) . DS . 'rsa_private.pem'
        );

        $this->getIo()->success('Key created');

        return Command::SUCCESS;
    }

    protected function generateCSR($country, $state, $locality, $organization, $commonName)
    {
        $privateKey = \openssl_pkey_new(array(
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'rsa_key_bits' => 2048,
        ));

        $csrDetails = array(
            'countryName' => $country,
            'stateOrProvinceName' => $state,
            'localityName' => $locality,
            'organizationName' => $organization,
            'commonName' => $commonName,
        );

        $csr = \openssl_csr_new($csrDetails, $privateKey);

        \openssl_pkey_export($privateKey, $privateKeyContents);
        file_put_contents(App::getDir(App::ASSETS) . DS . 'rsa_private.key', $privateKeyContents);

        \openssl_csr_export($csr, $csrContents);
        file_put_contents(App::getDir(App::ASSETS) . DS . 'rsa_private.csr', $csrContents);
    }

    protected function generateCertificate($csrPath, $privateKeyPath, $outputPath, $days = 365)
    {
        $csr = file_get_contents($csrPath);
        $privateKey = file_get_contents($privateKeyPath);

        $cert = \openssl_csr_sign($csr, null, $privateKey, $days, array('digest_alg' => 'sha256'));

        \openssl_x509_export($cert, $certificate);
        file_put_contents($outputPath, $certificate);
    }
}
