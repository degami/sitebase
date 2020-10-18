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

use \App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Configuration;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use \Exception;

/**
 * Edit Configuration Command
 */
class Edit extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Edit a config')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_OPTIONAL),
                        new InputOption('value', null, InputOption::VALUE_OPTIONAL),
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $io->error('Invalid config id');
            return;
        }

        $configuration = $this->getContainer()->call([Configuration::class, 'load'], ['id' => $id]);

        if (!$configuration->isLoaded()) {
            $io->error('Config does not exists');
            return;
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
            $configuration->value = $value;
            $configuration->persist();

            $output->writeln('<info>Config added</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n" . $e->getMessage() . "\n</error>");
        }
    }
}
