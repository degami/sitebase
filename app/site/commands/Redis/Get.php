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

namespace App\Site\Commands\Redis;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

/**
 * Information Statistics Command
 */
class Get extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Get Redis Entry')
        ->setDefinition(
            new InputDefinition(
                [
                    new InputOption('key', 'k', InputOption::VALUE_OPTIONAL),
                ]
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if (getenv('REDIS_CACHE', 0) == 0) {
            $this->getIo()->error('Redis cache is not enabled');
            return Command::FAILURE;
        }

        try {
            $client = $this->getRedis();
        } catch (\Exception $e) {
            $this->getIo()->error('Can\'t connect to redis server');
            return Command::FAILURE;
        }
        
        $key = $this->keepAskingForOption('key', 'Cache item key? ');

        $output->writeln($client->get($key));

        return Command::SUCCESS;
    }
}
