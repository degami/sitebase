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

namespace App\Base\Commands\Media;

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Models\MediaElement;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use SplFileInfo;

class Rescan extends BaseCommand
{
    protected function configure()
    {
        $this->setDescription('Rescan filesystem for missing media elements');
    }

    /**
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $media_path = App::getDir(App::MEDIA);
        $this->getIo()->writeln("Rescanning media folder: {$media_path}");

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($media_path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $created = 0;
        $skipped = 0;

        foreach ($rii as $item) {
            /** @var SplFileInfo $item */
            $path = $item->getPathname();
            $filename = $item->getFilename();

            if (str_starts_with($filename, '.')) {
                continue; // skip hidden files and directories
            }

             // check if MediaElement with this path already exists
            try {
                $existing = MediaElement::loadByPath($item->getPathname());

                // if not existing, an exception is thrown. If it nos throws, it means it exists
                $skipped++;
                continue;
            } catch (\Exception $e) {}


            $relativePath = str_replace($media_path . DIRECTORY_SEPARATOR, '', $path);

            $parentId = null;
            if (!empty(trim($relativePath, DS))) {
                try {
                    $parent = MediaElement::loadByPath(dirname($path));
                    $parentId = $parent->getId();
                } catch (Exception $e) {}
            }

            // Crea nuovo MediaElement
            try {
                /** @var MediaElement $media */
                $media = $this->containerMake(MediaElement::class);

                if ($item->isDir()) {
                    $mimetype = 'inode/directory';
                    $filesize = 0;
                } else {
                    $mimetype = mime_content_type($path) ?: 'application/octet-stream';
                    $filesize = filesize($path);
                }

                $media
                    ->setPath($path)
                    ->setFilename($filename)
                    ->setMimetype($mimetype)
                    ->setFilesize($filesize)
                    ->setParentId($parentId)
                    ->persist(); 

                $created++;
                $this->getIo()->writeln("Added missing MediaElement: {$relativePath}");
            } catch (\Throwable $e) {
                $this->getIo()->error("Error adding {$relativePath}: " . $e->getMessage());
            }
        }

        $this->getIo()->success("Rescan complete: created {$created} new media elements, skipped {$skipped} existing.");

        return Command::SUCCESS;
    }
}
