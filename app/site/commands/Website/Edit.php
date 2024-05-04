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

namespace App\Site\Commands\Website;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Website;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * Edit Website Command
 */
class Edit extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Edit a website')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_OPTIONAL),
                        new InputOption('name', 'n', InputOption::VALUE_OPTIONAL),
                        new InputOption('domain', 'd', InputOption::VALUE_OPTIONAL),
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
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid website id');
            return;
        }

        /** @var Website $website */
        $website = $this->getContainer()->call([Website::class, 'load'], ['id' => $id]);

        if (!$website->isLoaded()) {
            $this->getIo()->error('Website does not exists');
            return;
        }

        $name = $this->keepAskingForOption('name', 'Name? ');
        $domain = $this->keepAskingForOption('domain', 'Domain? ');

        if (!$this->confirmSave('Save Website? ')) {
            return;
        }

        try {
            $website->setSiteName($name);
            $website->setDomain($domain);

            $website->persist();

            $output->writeln('<info>Website saved</info>');
        } catch (Exception $e) {
            $output->writeln("<error>\n\n" . $e->getMessage() . "\n</error>");
        }
    }
}
