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

namespace App\Site\Commands\Users;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Site\Models\User;
use Symfony\Component\Console\Command\Command;

/**
 * Delete User Command
 */
class Delete extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Delete user')
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
            $this->getIo()->error('Invalid user id');
            return Command::FAILURE;
        }

        $user = $this->containerCall([User::class, 'load'], ['id' => $id]);

        if (!$user->isLoaded()) {
            $this->getIo()->error('User does not exists');
            return Command::FAILURE;
        }

        if ($id == 1) {
            $this->getIo()->error('User "' . $user->username . '" can\'t be deleted');
            return Command::FAILURE;
        }

        if (!$this->confirmDelete('Delete User "' . $user->getUsername() . '"? ')) {
            return Command::SUCCESS;
        }

        $user->delete();
        $this->getIo()->success('User deleted');

        return Command::SUCCESS;
    }
}
