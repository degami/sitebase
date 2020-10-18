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

namespace App\Site\Commands\Generate;

use \App\Base\Abstracts\Commands\CodeGeneratorCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Generate Block Command
 */
class Block extends CodeGeneratorCommand
{
    const BASE_NAMESPACE = "App\\Site\\Blocks\\";

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate a new Block class')
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
        $helper = $this->getHelper('question');
        $classname = $input->getOption('classname');
        while (trim($classname) == '') {
            $question = new Question('Class Name (starting from ' . static::BASE_NAMESPACE . ')? ');
            $classname = $helper->ask($input, $output, $question);
        }

        $this->addClass(static::BASE_NAMESPACE . $classname, $this->getFileContents($classname));

        $question = new ConfirmationQuestion('Save File(s) in ' . implode(", ", array_keys($this->filesToDump)) . '? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not Saving</info>');
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
     * @param string $className
     * @return string
     */
    protected function getFileContents($className)
    {
        return "<?php

namespace App\\Site\\Blocks;

use \\App\\Base\\Abstracts\\BaseCodeBlock;
use \\App\\Base\\Abstracts\\BasePage;

class " . $className . " extends BaseCodeBlock
{
    public function renderHTML(BasePage \$current_page)
    {
        return \"\";
    }
}
";
    }
}
