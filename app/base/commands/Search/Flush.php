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

namespace App\Base\Commands\Search;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Index data for search engine
 */
class Flush extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Flush data from elasticsearch index');
        $this->addOption('drop', 'd', InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if (!$this->getSearch()->isEnabled()) {
            $this->getIo()->error('Elasticsearch is not enabled');
            return Command::FAILURE;
        }

        if (!$this->getSearch()->checkService()) {
            $this->getIo()->error('Service is not available');

            return Command::FAILURE;
        }

        if ($input->getOption('drop')) {
            $this->getIo()->info('Dropping index');
            $this->getSearch()->dropIndex();
        } else {
            $this->getSearch()->flushIndex();
        }

        $this->getIo()->success('Data flushed');

        return Command::SUCCESS;
    }
}
