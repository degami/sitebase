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

namespace App\Base\Commands\Config;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Models\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Delete Configuration Command
 */
class Delete extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Delete config')
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
            $this->getIo()->error('Invalid config id');
            return Command::FAILURE;
        }

        $configuration = $this->containerCall([Configuration::class, 'load'], ['id' => $id]);

        if (!$configuration->isLoaded()) {
            $this->getIo()->error('Config does not exists');
            return Command::FAILURE;
        }

        if ($configuration->is_system == true) {
            $this->getIo()->error('User "' . $configuration->getPath() . '" can\'t be deleted');
            return Command::FAILURE;
        }

        if (!$this->confirmDelete('Delete Config "' . $configuration->getPath() . '"? ')) {
            return Command::SUCCESS;
        }

        $configuration->delete();
        $this->getIo()->success('User deleted');

        return Command::SUCCESS;
    }
}
