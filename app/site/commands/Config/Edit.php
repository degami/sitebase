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
                        new InputOption('path', 'p', InputOption::VALUE_OPTIONAL),
                        new InputOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale', null),
                        new InputOption('value', null, InputOption::VALUE_OPTIONAL),
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
            $configuration = null;
            $path = $input->getOption('path') ?? null;

            if ($path) {
                $condition = ['path' => $path];
                $locale = $input->getOption('locale');
                if ($locale) {
                    $condition['locale'] = $locale;
                }
                $website = $input->getOption('website');
                if (is_numeric($website)) {
                    $condition['website_id'] = $website;
                }
                $configuration = $this->containerCall([Configuration::class, 'loadByCondition'], ['condition' => $condition]);
            }

            if (!$configuration || !$configuration->getId()) {
                $this->getIo()->error('Invalid config id');
                return Command::FAILURE;    
            }

            $id = $configuration->getId();
        }

        /** @var Configuration $configuration */
        $configuration = $this->containerCall([Configuration::class, 'load'], ['id' => $id]);
        if (!$configuration->isLoaded()) {
            $this->getIo()->error('Config does not exists');
            return Command::FAILURE;
        }

        $value = $this->keepAskingForOption('value', 'Value? ');

        if (!$this->confirmSave('Save Config? ')) {
            return Command::SUCCESS;
        }

        try {
            $configuration->setValue($value);
            $configuration->persist();

            $this->getIo()->success('Config added');
        } catch (Exception $e) {
            $this->getIo()->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
