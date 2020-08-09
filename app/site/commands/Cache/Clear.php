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
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Cache Clear Command
 */
class Clear extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Clear Cache');
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getCache()->clear();
        $output->writeln('<info>Cache flushed.</info>');
    }
}
