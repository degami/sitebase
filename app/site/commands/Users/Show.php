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

namespace App\Site\Commands\Users;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Site\Models\User;
use Symfony\Component\Console\Command\Command;

/**
 * Show Users Command
 */
class Show extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Users list');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->renderTitle('Users');
        $this->renderTable(['ID', 'Username', 'Email', 'Roles'], array_map(fn($user) => [
            '<info>' . $user->getId() . '</info>',
            $user->getUsername(),
            $user->getEmail(),
            $user->getRole()->getName(),
        ], User::getCollection()->getItems()));

        return Command::SUCCESS;
    }
}
