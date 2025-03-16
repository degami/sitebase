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

namespace App\Base\Commands\Roles;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Models\Role;
use Symfony\Component\Console\Command\Command;

/**
 * Delete Role Command
 */
class Delete extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Delete role')
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
            $this->getIo()->error('Invalid role id');
            return Command::FAILURE;
        }

        $role = $this->containerCall([Role::class, 'load'], ['id' => $id]);

        if (!$role->isLoaded()) {
            $this->getIo()->error('Role does not exists');
            return Command::FAILURE;
        }

        if ($role->getName() == 'admin') {
            $this->getIo()->error('Role "' . $role->getName() . '" can\'t be deleted');
            return Command::FAILURE;
        }

        if (!$this->confirmDelete('Delete Role "' . $role->getName() . '"? ')) {
            return Command::SUCCESS;
        }

        $role->delete();
        $this->getIo()->success('Role deleted');

        return Command::SUCCESS;
    }
}
