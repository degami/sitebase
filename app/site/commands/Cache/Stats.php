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
namespace App\Site\Commands\Cache;

use \App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableCell;
use \Symfony\Component\Console\Helper\TableSeparator;
use \App\Base\Tools\Cache\Manager as CacheManager;

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
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Cache stats.</info>');

        $table = new Table($output);

        $table
            ->addRow(
                [new TableCell(
                    $this->getCache()->getStats()->getInfo()."\n".
                    "Cache size: " . $this->getCache()->getStats()->getSize()."\n".
                    "Cache Lifetime: ".$this->getCache()->getCacheLifetime(),
                    ['colspan' => 3]
                )]
            );

        $table
            ->addRow(new TableSeparator())
            ->addRow(['<info>Key</info>', '<info>Size</info>', '<info>Is Expired</info>']);

        foreach ($this->getCache()->getAllItemsByTag(CacheManager::CACHE_TAG) as $key => $item) {
            if ($item->getLength() > 0) {
                $table
                    ->addRow(new TableSeparator())
                    ->addRow([$key, $item->getLength(), $item->isExpired() ? 'true':'false']);
            }
        }

        $table->render();
    }
}
