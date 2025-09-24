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
use App\Base\Models\Role;
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Models\User;
use Exception;
use Symfony\Component\Console\Command\Command;

/**
 * Add User Command
 */
class Add extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add a new user')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('username', 'u', InputOption::VALUE_OPTIONAL),
                        new InputOption('email', 'e', InputOption::VALUE_OPTIONAL),
                        new InputOption('password', 'p', InputOption::VALUE_OPTIONAL),
                        new InputOption('role', 'r', InputOption::VALUE_OPTIONAL),
                        new InputOption('locale', 'l', InputOption::VALUE_OPTIONAL),
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
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $helper = $this->getQuestionHelper();

        $username = $this->keepAskingForOption('username', 'Username? ');
        $email = $this->keepAskingForOption('email', 'Email? ');

        $role = $input->getOption('role');
        if (empty($role)) {
            $role = $this->selectElementFromList(array_map(
                        function ($el) {
                            /** @var Role $el */
                            return $el->getName();
                        },
                        Role::getCollection()->getItems()
                    ), 'User Role? ');
        }

        $locale = $input->getOption('locale');
        if (empty($locale)) {
            $locale = $this->selectElementFromList($this->getUtils()->getSiteLanguagesSelectOptions(), 'User Locale? ');
        }

        $password = $this->keepAskingForOption('password', 'Password? ');

        $this->renderTitle('User Info');
        foreach (['username', 'email', 'role', 'locale', 'password'] as $key) {
            $output->writeln('<info>' . $key . ':</info> ' . ${$key});
        }

        if (!$this->confirmSave('Save User? ')) {
            return Command::SUCCESS;
        }

        try {
            /** @var User $user */
            $user = $this->containerCall([User::class, 'new']);
            $user->setUsername($username);
            $user->setNickname($username);
            $user->setEmail($email);
            $user->setPassword($this->getUtils()->getEncodedPass($password));
            $user->setLocale($locale);
            $user->setRole($role);
            $user->persist();

            $this->getIo()->success('User added');
        } catch (Exception $e) {
            $this->getIo()->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
