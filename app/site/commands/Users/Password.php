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
use Degami\Basics\Exceptions\BasicException;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use \App\Site\Models\User;

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
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIo();

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

        $password = $this->keepAskingForOption('password', 'Password? ');

        if (!$this->confirmSave('Save password? ')) {
            return;
        }

        $user->password = $this->getUtils()->getEncodedPass($password);
        $user->persist();

        $output->writeln('<info>Password changed</info>');
    }
}
