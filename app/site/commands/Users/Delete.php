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
use Symfony\Component\Console\Style\SymfonyStyle;
use \App\Site\Models\User;

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
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
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

        $user = $this->getContainer()->call([User::class, 'load'], ['id' => $id]);

        if (!$user->isLoaded()) {
            $io->error('User does not exists');
            return;
        }

        if ($id == 1) {
            $io->error('User "' . $user->username . '" can\'t be deleted');
            return;
        }

        $question = new ConfirmationQuestion('Delete User "' . $user->getUsername() . '"? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not deleted</info>');
            return;
        }

        $user->delete();
        $output->writeln('<info>User deleted</info>');
    }
}
