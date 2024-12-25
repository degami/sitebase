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
use Symfony\Component\Console\Command\Command;

/**
 * Generate Controller Command
 */
class Controller extends CodeGeneratorCommand
{
    public const BASE_NAMESPACE = "App\\Site\\Controllers\\";

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate a new Controller class')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('classname', 'c', InputOption::VALUE_OPTIONAL),
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
        $classname = $this->keepAskingForOption('classname', 'Class Name (starting from ' . static::BASE_NAMESPACE . ')? ');
        $this->addClass(static::BASE_NAMESPACE . $classname, $this->getFileContents($classname));

        if (!$this->confirmSave('Save File(s) in ' . implode(", ", array_keys($this->filesToDump)) . '? ')) {
            return Command::SUCCESS;
        }

        list($files_written, $errors) = array_values($this->doWrite());
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->getIo()->error($error);
            }
        } else {
            $this->getIo()->success(count($files_written) . ' File(s) saved');
        }

        return Command::SUCCESS;
    }

    /**
     * gets file content
     *
     * @param string $className
     * @return string
     */
    protected function getFileContents($className): string
    {
        return "<?php

namespace App\\Site\\Controllers;

use Psr\\Container\\ContainerInterface;
use App\\Base\\Abstracts\\Controllers\\FrontendPage;
use App\\App;

class " . $className . " extends FrontendPage
{
    protected \$template_data = [];

    /*
    public static function getRouteGroup(): string
    {
        return '';
    }

    public static function getRoutePath(): string
    {
        return '';
    }
    */

    protected function getTemplateName(): string
    {
        return '';
    }

    protected function getTemplateData(): array
    {
        return \$this->template_data;
    }
}
";
    }
}
