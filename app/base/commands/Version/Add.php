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

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\Console\Command\Command;
use App\Base\Abstracts\Models\BaseCollection;

/**
 * Add ModelVersion Command
 */
class Add extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add new version(s)')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('class', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
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
        $classnames = $input->getOption('class');
        if (empty($classnames)) {
            $classnames = array_merge(
                ClassFinder::getClassesInNamespace(App::BASE_MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
                ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE)
            );
        }

        $classnames = array_filter($classnames, function ($className) {
            return $this->containerCall([$className, 'canSaveVersions']);
        });

        if (empty($classnames)) {
            $this->getIo()->warning('No classes found that can save versions');
            return Command::FAILURE;
        }

        if (!$this->confirmMessage('Add new versions for classes: ' . implode(', ', $classnames) . ' ? ', 'Not saving versions')) {
            return Command::SUCCESS;
        }

        foreach ($classnames as $className) {
            try {
                /** @var BaseCollection $collection */
                $collection = $this->containerCall([$className, 'getCollection']);

                // persisting the collection will create a new version for all objects in it
                $collection->persist(['force_versioning' => true]);
            } catch (\Throwable $e) {
                $this->getIo()->error('Error adding version for class ' . $className . ': ' . $e->getMessage());
                continue;
            }
        }

        $this->getIo()->success('Version added');

        return Command::SUCCESS;
    }
}
