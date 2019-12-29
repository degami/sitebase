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
namespace App\Site\Commands\Config;

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
use \App\Site\Models\User;
use \Psr\Container\ContainerInterface;
use \Exception;

/**
 * Add Configuration Command
 */
class Add extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add a new config')
        ->setDefinition(
            new InputDefinition([
                new InputOption('path', null, InputOption::VALUE_OPTIONAL),
                new InputOption('value', null, InputOption::VALUE_OPTIONAL),
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

        $path = $input->getOption('path');
        while (trim($path) == '') {
            $question = new Question('Path? ');
            $path = $helper->ask($input, $output, $question);
        }

        $value = $input->getOption('value');
        while (trim($value) == '') {
            $question = new Question('Value? ');
            $value = $helper->ask($input, $output, $question);
        }

        $question = new ConfirmationQuestion('Save Config? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not Saving</info>');
            return;
        }

        try {
            $configuration = $this->getContainer()->call([\App\Site\Models\Configuration::class,'new']);
            $configuration->path = $path;
            $configuration->value = $value;
            $configuration->persist();

            $output->writeln('<info>Config added</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n".$e->getMessage()."\n</error>");
        }
    }
}
