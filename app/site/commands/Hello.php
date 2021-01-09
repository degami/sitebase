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
     * {@inheritdocs}
     *
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdocs}
     */
    protected function configure()
    {
        $this->setDescription('Say Hello');
        $this->addArgument('username', InputArgument::OPTIONAL, 'The name of the user to say hello.', null);
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
        if ($input->hasArgument('username')) {
            $output->writeln('<info>Hello ' . $input->getArgument('username') . '</info>');
        } else {
            $output->writeln('<info>Hello</info>');
        }
    }
}
