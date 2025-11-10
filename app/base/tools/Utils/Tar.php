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

namespace App\Base\Tools\Utils;

use App\Base\Abstracts\ContainerAwareObject;
use Exception;
use PharData;

/**
 * Tar utils functions Helper Class
 */
class Tar extends ContainerAwareObject
{
    /**
     * extract tar file to directory
     * 
     * @param string $tarPath
     * @param string $targetDirectory
     * @return void
     */
    public function extract(string $tarPath, string $targetDirectory): void
    {
        if (!class_exists('PharData')) {
            throw new Exception("Phar extension not available.");
        }

        if (!file_exists($tarPath)) {
            throw new Exception("Missing TAR file: $tarPath");
        }

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true)) {
            throw new Exception("Unable to create target directory: $targetDirectory");
        }

        try {
            $phar = new PharData($tarPath);
            $phar->extractTo($targetDirectory, null, true); // overwrite=true
        } catch (\Exception $e) {
            throw new Exception("Error extracting TAR: " . $e->getMessage());
        }
    }

    /**
     * compress a list of files
     * 
     * @param array $paths
     * @return PharData
     */
    public function compress(array $paths, string $archiveName = 'archive.tar'): PharData
    {
        if (empty($paths)) {
            throw new Exception("Missing files list.");
        }

        $tar = new \PharData($archiveName);

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            if (is_dir($path)) {
                $tar->buildFromDirectory($path);
            } else {
                $tar->addFile($path);
            }
        }

        return $tar;
    }
}