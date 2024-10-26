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

namespace App\Site\Commands\Config;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
use Symfony\Component\Console\Command\Command;

/**
 * Add Configuration Command
 */
class Add extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add a new config')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('path', null, InputOption::VALUE_OPTIONAL),
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
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $path = $this->keepAskingForOption('path', 'Path? ');
        $value = $this->keepAskingForOption('value', 'Value? ');

        if (!$this->confirmSave('Save Config? ')) {
            return Command::SUCCESS;
        }

        try {
            /** @var Configuration $configuration */
            $configuration = $this->containerCall([Configuration::class, 'new']);
            $configuration->setPath($path);
            $configuration->setValue($value);
            $configuration->persist();

            $this->getIo()->success('Config added');
        } catch (Exception $e) {
            $this->getIo()->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
