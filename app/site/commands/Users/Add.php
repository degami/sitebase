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
use App\Site\Models\Role;
use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Site\Models\User;
use Exception;

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
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getQuestionHelper();

        $username = $this->keepAskingForOption('username', 'Username? ');
        $email = $this->keepAskingForOption('email', 'Email? ');

        $role = $input->getOption('role');
        if (empty($role)) {
            $question = new ChoiceQuestion(
                'User Role? ',
                array_map(
                    function ($el) {
                        /** @var Role $el */
                        return $el->getName();
                    },
                    Role::getCollection()->getItems()
                ),
                0
            );
            $question->setErrorMessage('Role %s is invalid.');
            $role = $helper->ask($input, $output, $question);
        }

        $locale = $input->getOption('locale');
        if (empty($locale)) {
            $question = new ChoiceQuestion(
                'User Locale? ',
                $this->getUtils()->getSiteLanguagesSelectOptions(),
                0
            );
            $question->setErrorMessage('Locale %s is invalid.');
            $locale = $helper->ask($input, $output, $question);
        }

        $password = $this->keepAskingForOption('password', 'Password? ');

        $output->writeln('<info>User Info</info>');
        foreach (['username', 'email', 'role', 'locale', 'password'] as $key) {
            $output->writeln('<info>' . $key . ':</info> ' . ${$key});
        }

        if (!$this->confirmSave('Save User? ')) {
            return;
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

            $output->writeln('<info>User added</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n" . $e->getMessage() . "\n</error>");
        }
    }
}
