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
namespace App\Site\Commands\Roles;

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
use \App\Site\Models\Role;
use \Psr\Container\ContainerInterface;

/**
 * Grant Permission to Role Command
 */
class Grant extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Grant permission to role')
        ->setDefinition(
            new InputDefinition([
                new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
                new InputOption('permission', 'p', InputOption::VALUE_REQUIRED),
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
            $io->error('Invalid role id');
            return;
        }

        $role = $this->getContainer()->call([\App\Site\Models\Role::class,'load'], ['id' => $id]);

        if (!$role->isLoaded()) {
            $io->error('Role does not exists');
            return;
        }

        $permissions_available = array_filter(array_map(function ($el) use ($role) {
            if ($role->checkPermission($el->name)) {
                return '';
            }
            return $el->name;
        }, $this->getDb()->table('permission')->fetchAll()));

        if (empty($permissions_available)) {
            $io->error('No permission available to add');
            return;
        }

        $permission = $input->getOption('permission');
        if (empty($permission)) {
            $question = new ChoiceQuestion(
                'Role Permission? ',
                $permissions_available,
                0
            );
            $question->setErrorMessage('Permission %s is invalid.');
            $permission = $helper->ask($input, $output, $question);
        }
        $question = new ConfirmationQuestion('Add permission "'.$permission.'" to role "'.$role->getName().'"? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not saved</info>');
            return;
        }

        $role->grantPermission($permission);
        $output->writeln('<info>Role saved</info>');
    }
}