<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Site\Commands\Users;

use \App\Base\Abstracts\Commands\BaseCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableSeparator;
use \App\Site\Models\User;
use \Psr\Container\ContainerInterface;
use \Exception;

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
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $username = $input->getOption('username');
        while (trim($username) == '') {
            $question = new Question('Username? ');
            $username = $helper->ask($input, $output, $question);
        }

        $email = $input->getOption('email');
        while (trim($email) == '') {
            $question = new Question('Email? ');
            $email = $helper->ask($input, $output, $question);
        }

        $role = $input->getOption('role');
        if (empty($role)) {
            $question = new ChoiceQuestion(
                'User Role? ',
                array_map(
                    function ($el) {
                        return $el->name;
                    },
                    $this->getDb()->table('role')->fetchAll()
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

        $password = $input->getOption('password');
        while (trim($password) == '') {
            $question = new Question('Password? ');
            $password = $helper->ask($input, $output, $question);
        }

        $output->writeln('<info>User Info</info>');
        foreach (['username','email','role','locale','password'] as $key) {
            $output->writeln('<info>'.$key.':</info> '.${$key});
        }

        $question = new ConfirmationQuestion('Save User? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not Saving</info>');
            return;
        }

        try {
            $user = $this->getContainer()->call([\App\Site\Models\User::class,'new']);
            $user->username = $username;
            $user->nickname = $username;
            $user->email = $email;
            $user->password = $this->getUtils()->getEncodedPass($password);
            $user->locale = $locale;
            $user->setRole($role);
            $user->persist();

            $output->writeln('<info>User added</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n".$e->getMessage()."\n</error>");
        }
    }
}
