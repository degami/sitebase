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

namespace App\Base\Commands\Cache;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Tools\Cache\Manager as CacheManager;
use Symfony\Component\Console\Command\Command;

/**
 * Cache Statistics Command
 */
class Stats extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Cache stats');
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
        $tableContents = [
            [$this->getCache()->getStats()->getInfo() . "\n" .
            "Cache size: " . $this->getCache()->getStats()->getSize() . "\n" .
            "Cache Lifetime: " . $this->getCache()->getCacheLifetime()],
            ['<info>Key</info>', '<info>Size</info>', '<info>Is Expired</info>'],  
        ];

        foreach ($this->getCache()->getAllItemsByTag(CacheManager::CACHE_TAG) as $key => $item) {
            if ($item->getLength() > 0) {
                $tableContents[] = [$key, $item->getLength(), $item->isExpired() ? 'true' : 'false'];
            }
        }

        $this->renderTitle('Cache stats');
        $this->renderTable([], $tableContents);

        return Command::SUCCESS;
    }
}
