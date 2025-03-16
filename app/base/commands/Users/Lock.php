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
 * Lock User Command
 */
class Lock extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Lock user')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
                        new InputOption('until', 'u', InputOption::VALUE_OPTIONAL, 'lock until (date string or "forever") one hour starting from now if not specified', null),
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

        $until = trim($input->getOption('until'));
        if (strtolower($until) == 'forever') {
            $until = '9999-12-31 23:59:59';
        }
        if (!strtotime($until)) {
            $until = null;
        }

        $user->lock($until);
        $user->persist();

        $this->getIo()->success('User locked');
        
        return Command::SUCCESS;
    }
}
