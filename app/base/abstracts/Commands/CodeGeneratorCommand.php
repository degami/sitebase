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

namespace App\Base\Abstracts\Commands;

use Exception;
use Psr\Container\ContainerInterface;
use Nadar\PhpComposerReader\ComposerReader;
use Nadar\PhpComposerReader\AutoloadSection;
use App\App;

/**
 * Base for "generation" commands
 */
abstract class CodeGeneratorCommand extends BaseCommand
{
    /**
     * @var array files to dump
     */
    protected array $filesToDump = [];

    /**
     * @var ComposerReader composer reader
     */
    protected $composer_reader;

    /**
     * {@inheritdocs}
     *
     * @param string|null $name
     * @param ContainerInterface|null $container
     * @throws Exception
     */
    public function __construct($name = null, ContainerInterface $container = null)
    {
        parent::__construct($name, $container);
        $this->composer_reader = $this->getContainer()->make(ComposerReader::class, ['file' => App::getDir('root') . DS . 'composer.json']);

        if (!$this->composer_reader->canRead()) {
            throw new Exception("Unable to read json.");
        }
    }

    /**
     * add class
     *
     * @param string $full_class_name
     * @param string $file_contents
     * @return self
     */
    protected function addClass(string $full_class_name, string $file_contents): CodeGeneratorCommand
    {
        $arr = explode("\\", ltrim($full_class_name, "\\"));
        $className = array_pop($arr);
        $nameSpace = implode("\\", array_slice(explode("\\", ltrim($full_class_name, "\\")), 0, -1)) . "\\";

        $section = new AutoloadSection($this->composer_reader, AutoloadSection::TYPE_PSR4);

        $directory = '';
        $psr4 = [];
        foreach ($section as $autoload) {
            if (!preg_match("/^App\\\\/", $autoload->namespace)) {
                continue;
            }
            $psr4[$autoload->namespace] = App::getDir('root') . DS . implode(DS, explode("/", $autoload->source));
        }
        arsort($psr4);

        foreach ($psr4 as $ns => $dir) {
            if (preg_match("/^" . addslashes($ns) . "/i", $nameSpace)) {
                $directory = $dir . DS . str_replace("\\", DS, str_replace($ns, "", $nameSpace));
                break;
            }
        }

        if (!empty($directory)) {
            $filepath = $directory . (($directory[strlen($directory) - 1] == DS) ? '' : DS) . $className . '.php';
            $this->queueFile($filepath, $file_contents);
        }

        return $this;
    }

    /**
     * queue file to dump
     *
     * @param string $file_name
     * @param string $file_contents
     * @return self
     */
    protected function queueFile(string $file_name, string $file_contents): CodeGeneratorCommand
    {
        $this->filesToDump[$file_name] = $file_contents;
        return $this;
    }

    /**
     * flush files to disk
     *
     * @return array
     */
    protected function doWrite(): array
    {
        $files_written = $errors = [];
        foreach ($this->filesToDump as $path => $file_contents) {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 755, true)) {
                    $message = 'Can\'t create directory ' . $directory;
                    if (!in_array($message, $errors)) {
                        $errors[] = $message;
                    }
                    continue;
                }
            }
            if (is_dir($directory) && file_put_contents($path, (string)$file_contents, LOCK_EX)) {
                $files_written[] = $path;
            } else {
                $message = 'Can\'t write file ' . $path;
                if (!in_array($message, $errors)) {
                    $errors[] = $message;
                }
            }
        }

        return ['files_written' => $files_written, 'errors' => $errors];
    }
}
