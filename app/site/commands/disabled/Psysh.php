<?php

namespace App\Site\Commands;

use \Psy\Shell;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

final class Psysh extends Command
{
    /**
     * @var Shell
     */
    private $shell;

    /**
     * Psysh constructor.
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->shell = new Shell();
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Start PsySH');
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->shell->run();
    }
}
