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
use ZipArchive;

/**
 * Zip utils functions Helper Class
 */
class Zip extends ContainerAwareObject
{
    /**
     * extract zip file to directory
     * 
     * @param string $zipPath
     * @param string $targetDirectory
     * @return void
     */
    public function extract(string $zipPath, string $targetDirectory) : void
    {
        if (!file_exists($zipPath)) {
            throw new Exception("Missing ZIP file: $zipPath");
        }

        if (!is_dir($targetDirectory)) {
            if (!mkdir($targetDirectory, 0755, true)) {
                throw new Exception("Unable to create target directory: $targetDirectory");
            }
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) === true) {
            if ($zip->extractTo($targetDirectory)) {
                $zip->close();
            } else {
                $zip->close();
                throw new Exception("Errors extracting zip file.");
            }
        } else {
            throw new Exception("Unable to open ZIP file: $zipPath");
        }
    }

    /**
     * compress a list of files
     * 
     * @param array $paths
     * @return ZipArchive
     */
    public function compress(array $paths) : ZipArchive
    {
        if (empty($paths)) {
            throw new Exception("Missing files list.");
        }

        $zip = new ZipArchive();

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $zip->addFile($path);
        }

        return $zip;
    }
}