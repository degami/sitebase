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

/**
 * Generate Block Command
 */
class Block extends CodeGeneratorCommand
{
    public const BASE_NAMESPACE = "App\\Site\\Blocks\\";

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
        $classname = $this->keepAskingForOption('classname', 'Class Name (starting from ' . static::BASE_NAMESPACE . ')? ');
        $this->addClass(static::BASE_NAMESPACE . $classname, $this->getFileContents($classname));

        if (!$this->confirmSave('Save File(s) in ' . implode(", ", array_keys($this->filesToDump)) . '? ')) {
            return;
        }

        list($files_written, $errors) = array_values($this->doWrite());
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->getIo()->error($error);
            }
        } else {
            $this->getIo()->success(count($files_written) . ' File(s) saved');
        }
    }

    /**
     * gets file contents
     *
     * @param string $className
     * @return string
     */
    protected function getFileContents($className): string
    {
        return "<?php

namespace App\\Site\\Blocks;

use App\\Base\\Abstracts\\Blocks\\BaseCodeBlock;
use App\\Base\\Abstracts\\Controllers\\BasePage;

class " . $className . " extends BaseCodeBlock
{
    public function renderHTML(BasePage \$current_page = null, \$data = []): string
    {
        return \"\";
    }
}
";
    }
}
