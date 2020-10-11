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
namespace App\Site\Commands\Website;

use \App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Configuration;
use App\Site\Models\Website;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use \Exception;

/**
 * Add Website Command
 */
class Add extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add a new website')
            ->setDefinition(
                new InputDefinition(
                    [
                    new InputOption('name', null, InputOption::VALUE_OPTIONAL),
                    new InputOption('domain', null, InputOption::VALUE_OPTIONAL),
                    ]
                )
            );
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
        $helper = $this->getHelper('question');

        $name = $input->getOption('name');
        while (trim($name) == '') {
            $question = new Question('Name? ');
            $name = $helper->ask($input, $output, $question);
        }

        $domain = $input->getOption('domain');
        while (trim($domain) == '') {
            $question = new Question('Domain? ');
            $domain = $helper->ask($input, $output, $question);
        }

        $question = new ConfirmationQuestion('Save Website? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not Saving</info>');
            return;
        }

        try {
            $website = $this->getContainer()->call([Website::class,'new']);
            $website->site_name = $name;
            $website->domain = $domain;
            $website->persist();

            foreach ($this->getDb()->table('configuration')->where(['is_system' => 1, 'website_id' => 1])->fetchAll() as $config) {
                // copy at least is_system configurations
                $configuration_model = Configuration::new($this->getContainer());
                $configuration_model->website_id = $website->id;
                $configuration_model->path = $config->path;
                $configuration_model->value = '';
                $configuration_model->is_system = 1;
                $configuration_model->persist();
            }

            $output->writeln('<info>Website added</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n".$e->getMessage()."\n</error>");
        }
    }
}
