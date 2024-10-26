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
use Redis as RedisClient;
use Symfony\Component\Console\Command\Command;

/**
 * Information Statistics Command
 */
class Flush extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Flush Redis');
    }

    /**
     * {@inheritdocs}
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

        $client->flushAll();
        $this->getIo()->success('Redis Flushed');

        return Command::SUCCESS;
    }
}
