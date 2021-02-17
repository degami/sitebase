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

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Cache Get Element Command
 */
class Get extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Get Cache item')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('key', 'k', InputOption::VALUE_OPTIONAL),
                        new InputOption('format', 'f', InputOption::VALUE_OPTIONAL),
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $this->keepAskingForOption('key', 'Cache item key? ');

        $format = $input->getOption('format');
        $callback = null;
        switch ($format) {
            case 'json':
                $callback = 'json_encode';
                break;
            case 'serialize':
            default:
                $callback = 'serialize';
                break;
        }

        $output->writeln($callback($this->getCache()->get($key)));
    }
}
