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

namespace App\Site\Commands\TwoFa;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

/**
 * Cache Disable Command
 */
class Disable extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Disable 2 Factory Authentication')
        ->setDefinition(
            new InputDefinition(
                [
                    new InputOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type: admin/frontend', 'admin'),
                ]
            )
        );
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws PhpfastcacheSimpleCacheException
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {

        $key = match($input->getOption('type')) {
            'frontend' => 'USE2FA_USERS',
            default => 'USE2FA_ADMIN',
        };

        $argInput = new ArrayInput([
            // the command name is passed as first argument
            'command' => 'app:mod_env',
            '--key'  => $key,
            '--value' => 0,
        ]);

        $this->getApplication()->run($argInput, $output);

        return Command::SUCCESS;
    }
}
