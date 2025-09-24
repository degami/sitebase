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
use App\Base\Models\Permission;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Models\Role;
use Symfony\Component\Console\Command\Command;

/**
 * Grant Permission to Role Command
 */
class Grant extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Grant permission to role')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
                        new InputOption('permission', 'p', InputOption::VALUE_REQUIRED),
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
     * @throws BasicException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid role id');
            return Command::FAILURE;
        }

        /** @var Role $role */
        $role = $this->containerCall([Role::class, 'load'], ['id' => $id]);

        if (!$role->isLoaded()) {
            $this->getIo()->error('Role does not exists');
            return Command::FAILURE;
        }

        $permissions_available = array_filter(
            array_map(
                function ($el) use ($role) {
                    /** @var Permission $el */
                    if ($role->checkPermission($el->getName())) {
                        return '';
                    }
                    return $el->getName();
                },
                Permission::getCollection()->getItems()
            )
        );

        if (empty($permissions_available)) {
            $this->getIo()->error('No permission available to add');
            return Command::FAILURE;
        }

        $permission = $input->getOption('permission');
        if (empty($permission)) {
            $permission = $this->selectElementFromList($permissions_available, 'Role Permission? ');
        }

        if (!$this->confirmSave('Add permission "' . $permission . '" to role "' . $role->getName() . '"? ')) {
            return Command::SUCCESS;
        }

        $role->grantPermission($permission);
        $this->getIo()->success('Role saved');

        return Command::SUCCESS;
    }
}
