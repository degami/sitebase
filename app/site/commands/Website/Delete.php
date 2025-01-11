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

namespace App\Site\Commands\Website;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Website;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Delete Website Command
 */
class Delete extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Delete website')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
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
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid website id');
            return Command::FAILURE;
        }

        $website = $this->containerCall([Website::class, 'load'], ['id' => $id]);

        if (!$website->isLoaded()) {
            $this->getIo()->error('Website does not exists');
            return Command::FAILURE;
        }

        if (!$this->confirmDelete('Delete Website "' . $website->getSiteName() . '"? ')) {
            return Command::SUCCESS;
        }

        $website->delete();
        $this->getIo()->success('Website deleted');

        return Command::SUCCESS;
    }
}
