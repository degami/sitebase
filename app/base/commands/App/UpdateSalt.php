<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Commands\App;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use InvalidArgumentException;

/**
 * Update Salt Command
 */
class UpdateSalt extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Change SALT value into .env file');
    }

        /**
     * {@inheritdoc}
     *
     * @return true
     */
    public static function registerCommand(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if (!file_exists('.env')) {
            $this->getIo()->error(".env file is missing!");
            return Command::FAILURE;
        }

        // Ottieni l'istanza dell'applicazione
        $application = $this->getApplication();

        if ($application === null) {
            $output->writeln('<error>Errors loading Application!</error>');
            return Command::FAILURE;
        }

        // Recupera il secondo comando
        $command = $application->find('app:mod_env');

        // Crea l'input per il secondo comando
        $arguments = [
            'command' => 'app:mod_env',
            '--key' => 'SALT',
            '--value' => $this->getSalt(),
        ];
        $arrayInput = new ArrayInput($arguments);

        // Esegui il secondo comando
        $returnCode = $command->run($arrayInput, $output);

        if ($returnCode !== Command::SUCCESS) {
            $output->writeln('<error>Errors executing modEnv command!</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function getSalt(int $length = 16)
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('Lenght must be a number higher than zero.');
        }
    
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
    
        return $randomString;
    }
}
