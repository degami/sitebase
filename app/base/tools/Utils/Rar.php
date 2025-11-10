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
use RarArchive;

/**
 * RAR utils functions Helper Class
 */
class Rar extends ContainerAwareObject
{
    /**
     * extract rar file to directory
     * 
     * @param string $rarPath
     * @param string $targetDirectory
     * @return void
     */
    public function extract(string $rarPath, string $targetDirectory) : void
    {
        if (!class_exists(RarArchive::class)) {
            throw new Exception("RarArchive class not found. Please enable rar extension.");
        }

        if (!file_exists($rarPath)) {
            throw new Exception("Missing RAR file: $rarPath");
        }

        if (!is_dir($targetDirectory)) {
            if (!mkdir($targetDirectory, 0755, true)) {
                throw new Exception("Unable to create target directory: $targetDirectory");
            }
        }

        $rar = new RarArchive();

        if ($rar->open($rarPath) === true) {
            if ($rar->extractTo($targetDirectory)) {
                $rar->close();
            } else {
                $rar->close();
                throw new Exception("Errors extracting rar file.");
            }
        } else {
            throw new Exception("Unable to open RAR file: $rarPath");
        }
    }

    /**
     * compress a list of files
     * 
     * @param array $paths
     * @return RarArchive
     */
    public function compress(array $paths) : RarArchive
    {
        if (empty($paths)) {
            throw new Exception("Missing files list.");
        }

        $rar = new RarArchive();

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $rar->addFile($path);
        }

        return $rar;
    }
}