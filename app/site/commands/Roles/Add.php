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
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableSeparator;
use \App\Site\Models\Role;
use \Psr\Container\ContainerInterface;
use \Exception;

/**
 * Add Role Command
 */
class Add extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add a new role')
        ->setDefinition(
            new InputDefinition([
                new InputOption('name', '', InputOption::VALUE_OPTIONAL),
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
        $helper = $this->getHelper('question');

        $name = $input->getOption('name');
        while (trim($name) == '') {
            $question = new Question('Name? ');
            $name = $helper->ask($input, $output, $question);
        }

        $question = new ConfirmationQuestion('Save Role? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not Saving</info>');
            return;
        }

        try {
            $role = $this->getContainer()->call([\App\Site\Models\Role::class,'new']);
            $role->name = $name;
            $role->persist();

            $output->writeln('<info>Role added</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n".$e->getMessage()."\n</error>");
        }
    }
}
