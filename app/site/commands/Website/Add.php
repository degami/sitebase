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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $this->keepAskingForOption('name', 'Name? ');
        $domain = $this->keepAskingForOption('domain', 'Domain? ');

        if (!$this->confirmSave('Save Website? ')) {
            return;
        }

        try {
            /** @var Website $website */
            $website = $this->getContainer()->call([Website::class, 'new']);
            $website->setSiteName($name);
            $website->setDomain($domain);
            $website->persist();

            foreach ($this->getContainer()->call([Configuration::class, 'where'], ['condition' => ['is_system' => 1, 'website_id' => 1]]) as $config) {
                /** @var Configuration $config */

                // copy at least is_system configurations

                /** @var Configuration $configuration_model */
                $configuration_model = $this->getContainer()->call([Configuration::class, 'new'], ['initial_data' => [
                    'website_id' => $website->getId(),
                    'path' => $config->getPath(),
                    'value' => '',
                    'is_system' => 1,
                ]]);

                $configuration_model->persist();
            }

            $output->writeln('<info>Website added</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n" . $e->getMessage() . "\n</error>");
        }
    }
}
