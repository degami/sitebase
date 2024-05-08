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

namespace App\Site\Commands\Db;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Genkgo\Migrations\Adapters\AbstractPdoAdapter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Container\ContainerInterface;
use App\Base\Overrides\Migrations\Factory;
use Genkgo\Migrations\Adapters\PdoMysqlAdapter;
use Genkgo\Migrations\MigrationInterface;
use App\App;

/**
 * Migrate Database Command
 */
class Migrate extends BaseCommand
{
    /**
     * @var AbstractPdoAdapter adapter
     */
    protected $adapter;

    /**
     * @var Factory factory
     */
    protected $factory;

    /**
     * @var string directory
     */
    protected $directory;

    /**
     * {@inheritdocs}
     *
     * @param string|null $name
     * @param ContainerInterface|null $container
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        protected ContainerInterface $container,
        $name = null
    ) {
        parent::__construct($container, $name);
        $this->adapter = $this->containerMake(PdoMysqlAdapter::class, ['pdo' => $this->getPdo()]);
        $this->factory = $this->containerMake(
            Factory::class,
            [
                'adapter' => $this->adapter,
                'classLoader' => function ($classname) use ($container) {
                    return new $classname($container);
                },
            ]
        );
        $this->directory = App::getDir(App::MIGRATIONS);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Migrate')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('direction', 'd', InputOption::VALUE_OPTIONAL),
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
        $direction = $input->getOption('direction');
        if ($direction == 'down') {
            $direction = MigrationInterface::DIRECTION_DOWN;
        } else {
            $direction = MigrationInterface::DIRECTION_UP;
        }

        $list = $this->factory->newListFromDirectory($this->directory, 'App\\Site\\Migrations\\');
        $result = $list->migrate($direction);

        foreach ($result as $item) {
            $output->writeln($item->getName());
        }

        $this->getIo()->success('Migration done.');
    }
}
