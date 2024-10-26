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

namespace App\Site\Commands\Roles;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Site\Models\Role;
use Symfony\Component\Console\Command\Command;

/**
 * Revoke Permission from Role Command
 */
class Revoke extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Revoke permission to role')
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
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid role id');
            return Command::FAILURE;
        }

        /** @var Role $role */
        $role = $this->containerCall([Role::class, 'load'], ['id' => $id]);

        if (!$role->isLoaded()) {
            $this->getIo()->error('Role does not exists');
            return Command::FAILURE;
        }

        $permissions_available = array_map(
            function ($el) {
                return $el->getName();
            },
            $role->getPermissionsArray()
        );

        if (empty($permissions_available)) {
            $this->getIo()->error('No permission available to revoke');
            return Command::FAILURE;
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

        if (!$this->confirmSave('Revoke permission "' . $permission . '" to role "' . $role->getName() . '"? ')) {
            return Command::SUCCESS;
        }

        $role->revokePermission($permission);
        $this->getIo()->success('Role saved');

        return Command::SUCCESS;
    }
}
