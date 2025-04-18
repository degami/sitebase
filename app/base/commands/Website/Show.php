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

namespace App\Base\Commands\Website;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Models\Website;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Show Website Command
 */
class Show extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Show Websites');
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
        $this->renderTitle('Websites');
        $this->renderTable(['ID', 'Name', 'Domain'], array_map(fn($website) => [
            $website->getId(),
            $website->getSiteName(),
            $website->getDomain()
        ], Website::getCollection()->getItems()));

        return Command::SUCCESS;
    }
}
