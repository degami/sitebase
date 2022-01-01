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

namespace App\Site\Commands\Generate;

use App\Base\Abstracts\Commands\CodeGeneratorCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Generate Command Command
 */
class Command extends CodeGeneratorCommand
{
    public const BASE_NAMESPACE = "App\\Site\\Commands\\";

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate a new Command class')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('classname', 'c', InputOption::VALUE_OPTIONAL),
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
        $classname = $this->keepAskingForOption('classname', 'Class Name (starting from ' . static::BASE_NAMESPACE . ')? ');
        $this->addClass(static::BASE_NAMESPACE . $classname, $this->getFileContents(static::BASE_NAMESPACE, $classname));

        if (!$this->confirmSave('Save File(s) in ' . implode(", ", array_keys($this->filesToDump)) . '? ')) {
            return;
        }

        list($files_written, $errors) = array_values($this->doWrite());
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $output->writeln("<error>\n\n " . $error . "\n</error>");
            }
        } else {
            $output->writeln('<info>' . count($files_written) . ' File(s) saved</info>');
        }
    }

    /**
     * gets file contents
     *
     * @param string $nameSpace
     * @param string $className
     * @return string
     */
    protected function getFileContents($nameSpace, $className): string
    {
        return "<?php

namespace " . trim($nameSpace, "\\") . ";

use App\\Base\\Abstracts\\Commands\\BaseCommand;
use Symfony\\Component\\Console\\Input\\InputInterface;
use Symfony\\Component\\Console\\Output\\OutputInterface;
use Psr\\Container\\ContainerInterface;

class " . $className . " extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        \$this->setDescription('" . $className . "');
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface \$input, OutputInterface \$output)
    {
    }
}
";
    }
}
