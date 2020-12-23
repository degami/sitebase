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

use \App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use \App\Site\Models\Role;

/**
 * Grant Permission to Role Command
 */
class Grant extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Grant permission to role')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
                        new InputOption('permission', 'p', InputOption::VALUE_REQUIRED),
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
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid role id');
            return;
        }

        /** @var Role $role */
        $role = $this->getContainer()->call([Role::class, 'load'], ['id' => $id]);

        if (!$role->isLoaded()) {
            $this->getIo()->error('Role does not exists');
            return;
        }

        $permissions_available = array_filter(
            array_map(
                function ($el) use ($role) {
                    if ($role->checkPermission($el->name)) {
                        return '';
                    }
                    return $el->name;
                },
                $this->getDb()->table('permission')->fetchAll()
            )
        );

        if (empty($permissions_available)) {
            $this->getIo()->error('No permission available to add');
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
            $permission = $this->getQuestionHelper()->ask($input, $output, $question);
        }

        if (!$this->confirmSave('Add permission "' . $permission . '" to role "' . $role->getName() . '"? ')) {
            return;
        }

        $role->grantPermission($permission);
        $output->writeln('<info>Role saved</info>');
    }
}
