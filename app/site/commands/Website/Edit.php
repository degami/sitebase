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
use Symfony\Component\Console\Command\Command;

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
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $id = $input->getOption('id');
        if (!is_numeric($id)) {
            $this->getIo()->error('Invalid website id');
            return Command::FAILURE;
        }

        /** @var Website $website */
        $website = $this->containerCall([Website::class, 'load'], ['id' => $id]);

        if (!$website->isLoaded()) {
            $this->getIo()->error('Website does not exists');
            return Command::FAILURE;
        }

        $name = $this->keepAskingForOption('name', 'Name? ');
        $domain = $this->keepAskingForOption('domain', 'Domain? ');

        if (!$this->confirmSave('Save Website? ')) {
            return Command::SUCCESS;
        }

        try {
            $website->setSiteName($name);
            $website->setDomain($domain);

            $website->persist();

            $this->getIo()->success('Website saved');
        } catch (Exception $e) {
            $this->getIo()->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
