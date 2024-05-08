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

namespace App\Site\Commands\Roles;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Site\Models\Role;

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
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid role id');
            return;
        }

        $role = $this->containerCall([Role::class, 'load'], ['id' => $id]);

        if (!$role->isLoaded()) {
            $this->getIo()->error('Role does not exists');
            return;
        }

        if ($role->getName() == 'admin') {
            $this->getIo()->error('Role "' . $role->getName() . '" can\'t be deleted');
            return;
        }

        if (!$this->confirmDelete('Delete Role "' . $role->getName() . '"? ')) {
            return;
        }

        $role->delete();
        $this->getIo()->success('Role deleted');
    }
}
