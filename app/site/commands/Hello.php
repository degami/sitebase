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

namespace App\Site\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Hello Command
 */
class Hello extends Command
{
    /**
     * {@inheritdoc}
     *
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Say Hello');
        $this->addArgument('username', InputArgument::OPTIONAL, 'The name of the user to say hello.', null);
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
        if ($input->hasArgument('username')) {
            $output->writeln('<info>Hello ' . $input->getArgument('username') . '</info>');
        } else {
            $output->writeln('<info>Hello</info>');
        }

        return Command::SUCCESS;
    }
}
