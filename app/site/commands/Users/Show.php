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
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableSeparator;
use \App\Site\Models\User;
use \Psr\Container\ContainerInterface;

/**
 * Show Users Command
 */
class Show extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Users list');
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
        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Username','Email','Roles']);

        foreach ($this->getDb()->table('user')->fetchAll() as $k => $user_dbrow) {
            $user = $this->getContainer()->make(User::class)->fill($user_dbrow);

            if ($k > 0) {
                $table->addRow(new TableSeparator());
            }

            $table
                ->addRow(
                    [
                    '<info>'.$user->getId().'</info>',
                    $user->getUsername(),
                    $user->getEmail(),
                    $user->getRole()->getName(),
                    ]
                );
        }
        $table->render();
    }
}
