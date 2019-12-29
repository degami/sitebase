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
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableSeparator;
use \App\Site\Models\Role;
use \Psr\Container\ContainerInterface;

/**
 * Show Roles Command
 */
class Show extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Roles list');
    }

    /**
     * {@inheritdocs}
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Name','Permissions']);

        foreach ($this->getDb()->table('role')->fetchAll() as $k => $role_dbrow) {
            $role = $this->getContainer()->make(Role::class)->fill($role_dbrow);

            if ($k > 0) {
                $table->addRow(new TableSeparator());
            }

            $table
            ->addRow([
                '<info>'.$role->getId().'</info>',
                $role->getName(),
                implode("\n", array_map(function ($el) {
                    return $el->getName();
                }, $role->getPermissionsArray())),
            ]);
        }
        $table->render();
    }
}
