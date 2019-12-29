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

use \App\Base\Abstracts\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableSeparator;
use \App\Site\Models\User;
use \Psr\Container\ContainerInterface;

/**
 * Change User Password Command
 */
class Password extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Change user password')
        ->setDefinition(
            new InputDefinition([
                new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
                new InputOption('password', 'p', InputOption::VALUE_OPTIONAL),
            ])
        );
    }

    /**
     * {@inheritdocs}
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $io->error('Invalid user id');
            return;
        }

        $user = $this->getContainer()->call([\App\Site\Models\User::class,'load'], ['id' => $id]);

        if (!$user->isLoaded()) {
            $io->error('User does not exists');
            return;
        }

        $password = $input->getOption('password');
        while (trim($password) == '') {
            $question = new Question('Password? ');
            $password = $helper->ask($input, $output, $question);
        }

        $question = new ConfirmationQuestion('Save password? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not saved</info>');
            return;
        }

        $user->password = $this->getUtils()->getEncodedPass($password);
        $user->persist();
        
        $output->writeln('<info>Password changed</info>');
    }
}
