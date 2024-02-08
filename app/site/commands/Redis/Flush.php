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
use App\Site\Models\Block;
use App\Site\Models\Contact;
use App\Site\Models\ContactSubmission;
use App\Site\Models\LinkExchange;
use App\Site\Models\MailLog;
use App\Site\Models\MediaElement;
use App\Site\Models\RequestLog;
use App\Site\Models\User;
use App\Site\Models\Website;
use App\Site\Models\Page;
use App\Site\Models\News;
use App\Site\Models\Taxonomy;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Redis as RedisClient;

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (getenv('REDIS_CACHE', 0) == 0) {
            $output->writeln('<error>Redis cache is not enabled</error>');
            return;
        }
        $client = new RedisClient();
        $isConnected = $client->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'), 5);
        if (!$isConnected) {
            $output->writeln('<error>Can\'t connect to redis server</error>');
            return;
        }
        if (!empty(getenv('REDIS_PASSWORD', ''))) {
            $client->auth(getenv('REDIS_PASSWORD',''));
        }
        $client->select(getenv('REDIS_DATABASE'));

        $client->flushAll();
        $output->writeln('<info>Redis Flushed</info>');
    }
}
