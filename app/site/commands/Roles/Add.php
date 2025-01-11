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

namespace App\Site\Commands\Roles;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Site\Models\Role;
use Exception;
use Symfony\Component\Console\Command\Command;

/**
 * Add Role Command
 */
class Add extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Add a new role')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('name', '', InputOption::VALUE_OPTIONAL),
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
        $name = $this->keepAskingForOption('name', 'Name? ');

        if (!$this->confirmSave('Save Role? ')) {
            return Command::SUCCESS;
        }

        try {
            /** @var Role $role */
            $role = $this->containerCall([Role::class, 'new']);
            $role->setName($name);
            $role->persist();

            $this->getIo()->success('Role added');
        } catch (Exception $e) {
            $this->getIo()->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
