<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Commands\Version;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Models\ModelVersion;
use App\Base\Abstracts\Models\BaseModel;
use Symfony\Component\Console\Command\Command;

/**
 * Restore ModelVersion Command
 */
class Restore extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Restore version')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('id', 'i', InputOption::VALUE_REQUIRED),
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
            $this->getIo()->error('Invalid version id');
            return Command::FAILURE;
        }

        /** @var ModelVersion $version */
        $version = $this->containerCall([ModelVersion::class, 'load'], ['id' => $id]);

        if (!$version->isLoaded()) {
            $this->getIo()->error('Version does not exists');
            return Command::FAILURE;
        }

        if (!$this->confirmSave('Restore Version "' . $version->getId() . '" ('.$version->getCreatedAt().') ? ')) {
            return Command::SUCCESS;
        }

        /** @var BaseModel $object */
        $object = $version->getObject();
        $object->restoreVersion($version, true);

        $this->getIo()->success('Version restored');

        return Command::SUCCESS;
    }
}
