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
use Symfony\Component\Console\Output\OutputInterface;
use App\Site\Models\Role;

/**
 * Show Roles Command
 */
class Show extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this->setDescription('Roles list');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $this->renderTitle('Roles');
        $this->renderTable(['ID', 'Name', 'Permissions'], array_map(function($role) {
            return [
                '<info>' . $role->getId() . '</info>',
                $role->getName(),
                implode(
                    "\n",
                    array_map(
                        function ($el) {
                            return $el->getName();
                        },
                        $role->getPermissionsArray()
                    )
                ),
            ];
        }, Role::getCollection()->getItems()));
    }
}
