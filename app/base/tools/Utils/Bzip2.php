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

/**
 * Bzip2 utils functions Helper Class
 */
class Bzip2 extends ContainerAwareObject
{
    /**
     * Compress a single file into bzip2 format
     * 
     * @param string $sourceFile      Path to the file to compress
     * @param string|null $targetFile Optional target .bz2 file path (default: sourceFile.bz2)
     * @param int $level Compression level 0-9 (default 9 = max)
     * 
     * @return string Path to the created bzip2 file
     */
    public function compress(string $sourceFile, ?string $targetFile = null, int $level = 9): string
    {
        if (!function_exists('bzopen')) {
            throw new Exception("Bzip2 functions not found. Please enable bzip2 extension.");
        }

        if (!file_exists($sourceFile)) {
            throw new Exception("Source file not found: $sourceFile");
        }

        if ($targetFile === null) {
            $targetFile = $sourceFile . '.bz2';
        }

        $in = fopen($sourceFile, 'rb');
        if ($in === false) {
            throw new Exception("Unable to open source file: $sourceFile");
        }

        $out = bzopen($targetFile, 'wb'.$level);
        if ($out === false) {
            fclose($in);
            throw new Exception("Unable to open target bzip2 file: $targetFile");
        }

        while (!feof($in)) {
            $data = fread($in, 1024 * 512);
            if ($data === false) {
                fclose($in);
                bzclose($out);
                throw new Exception("Error reading source file during compression.");
            }
            bzwrite($out, $data);
        }

        fclose($in);
        bzclose($out);

        @unlink($sourceFile);

        return $targetFile;
    }

    /**
     * Extract a bzip2 file to a target file
     * 
     * @param string $bzip2File Path to the .bz2 file
     * @param string|null $targetFile Optional target file path (default: bzip2File without .bz2)
     * 
     * @return string Path to the extracted file
     */
    public function extract(string $bzip2File, ?string $targetFile = null): string
    {
        if (!function_exists('bzopen')) {
            throw new Exception("Bzip2 functions not found. Please enable bzip2 extension.");
        }

        if (!file_exists($bzip2File)) {
            throw new Exception("Bzip2 file not found: $bzip2File");
        }

        if ($targetFile === null) {
            $targetFile = preg_replace('/\.bz2$/', '', $bzip2File);
        }

        $in = bzopen($bzip2File, 'rb');
        if ($in === false) {
            throw new Exception("Unable to open bzip2 file: $bzip2File");
        }

        $out = fopen($targetFile, 'wb');
        if ($out === false) {
            bzclose($in);
            throw new Exception("Unable to open target file: $targetFile");
        }

        while (!feof($in)) {
            $data = fread($in, 1024 * 512);
            if ($data === false) {
                bzclose($in);
                fclose($out);
                throw new Exception("Error reading bzip2 file during extraction.");
            }
            fwrite($out, $data);
        }

        bzclose($in);
        fclose($out);

        @unlink($bzip2File);

        return $targetFile;
    }
}
