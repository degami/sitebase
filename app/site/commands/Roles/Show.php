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
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
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
        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Permissions']);

        $k = 0;
        foreach (Role::getCollection() as $role) {
            /** @var Role $role */

            if ($k++ > 0) {
                $table->addRow(new TableSeparator());
            }

            $table
                ->addRow(
                    [
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
                    ]
                );
        }
        $table->render();
    }
}
