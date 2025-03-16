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
use App\Base\Models\User2Fa;
use Symfony\Component\Console\Command\Command;

/**
 * Clear2Fa User Command
 */
class Clear2Fa extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('users:clear_2fa');
        $this->setDescription('Clear user 2fa secret')
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

        if ($user->getUser2Fa() == null) {
            $this->getIo()->error('User does not have 2Fa secret');
            return Command::FAILURE;
        }

        $collection = User2Fa::getCollection()->where(['user_id' => $user->getId()]);
        foreach($collection as $item) {
            $item->delete();
        }

        $this->getIo()->success('User 2Fa secret removed');

        return Command::SUCCESS;
    }
}
