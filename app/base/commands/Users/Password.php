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

namespace App\Base\Commands\Users;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Models\User;
use Symfony\Component\Console\Command\Command;

/**
 * Change User Password Command
 */
class Password extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Change user password')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
                        new InputOption('password', 'p', InputOption::VALUE_OPTIONAL),
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
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid user id');
            return Command::FAILURE;
        }

        /** @var User $user */
        $user = $this->containerCall([User::class, 'load'], ['id' => $id]);

        if (!$user->isLoaded()) {
            $this->getIo()->error('User does not exists');
            return Command::FAILURE;
        }

        $password = $this->keepAskingForOption('password', 'Password? ');

        if (!$this->confirmSave('Save password? ')) {
            return Command::SUCCESS;
        }

        $user->setPassword($this->getUtils()->getEncodedPass($password));
        $user->persist();

        $this->getIo()->success('Password changed');

        return Command::SUCCESS;
    }
}
