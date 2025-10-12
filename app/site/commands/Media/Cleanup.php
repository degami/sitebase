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

namespace App\Site\Commands\Media;

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\MediaElement;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use SplFileInfo;

/**
 * CleanUp Media Command
 */
class Cleanup extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Cleanup all media element without file or files without media element');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $confirm = $this->confirmDelete('Confirm deletion of media elements without file or files without media element? ');
        if (!$confirm) {
            return Command::SUCCESS;
        }

        $collection = MediaElement::getCollection();

        $validFiles = array_filter(array_map(function (MediaElement $media) {
            $path = realpath($media->getPath());
            if (!$path) {
                $media->delete();
                $this->getIo()->writeln('Deleted media element with id ' . $media->getId() . ' because file does not exist');
                return null;
            }

            return $path;
        }, $collection->getItems()));

        $this->purgeFileSystem($validFiles);

        return Command::SUCCESS;
    }

    protected function purgeFileSystem(array $validFiles = []) : array
    {
        $validFiles = array_filter(array_map('realpath', $validFiles));

        $validFilesMap = array_flip($validFiles);

        $media_path = App::getDir(App::MEDIA);
        $files = [];

        $dir = new \RecursiveDirectoryIterator($media_path, \FilesystemIterator::SKIP_DOTS);
        $rii = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::CHILD_FIRST);

        $deletedFiles = $deletedDirs = 0;

        foreach ($rii as $item) {
            /** @var SplFileInfo $item */
            $path = $item->getPathname();
            $filename = $item->getFilename();

            if (str_starts_with($filename, '.')) {
                continue; // skip hidden files and directories
            }

            if ($item->isFile()) {
                if (!isset($validFilesMap[realpath($path)])) {
                    $this->getIo()->writeln('Deleting file ' . $path . ' because no media element is linked to it');

                    try {
                        if (!@unlink($path)) {
                            $this->getIo()->warning("Failed to delete file $path");
                        } else {
                            $deletedFiles++;
                        }
                    } catch (\Throwable $e) {
                        $this->getIo()->error("Error deleting file $path: " . $e->getMessage());
                    }

                }
            } elseif ($item->isDir()) {
                if (!isset($validFilesMap[realpath($path)])) {
                    $contents = array_diff(scandir($path), ['.', '..']);
                    if (empty($contents)) {
                        $this->getIo()->writeln('Deleting empty directory ' . $path);

                        try {
                            if (!@rmdir($path)) {
                                $this->getIo()->warning("Failed to delete directory $path");
                            } else {
                                $deletedDirs++;
                            }
                        } catch (\Throwable $e) {
                            $this->getIo()->error("Error deleting directory $path: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->getIo()->success("Cleanup completed: deleted $deletedFiles files and $deletedDirs directories.");

        return $files;
    }
}
