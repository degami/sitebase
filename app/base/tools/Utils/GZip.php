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
 * GZip utils functions Helper Class
 */
class GZip extends ContainerAwareObject
{
    /**
     * Compress a single file into gzip format
     * 
     * @param string $sourceFile      Path to the file to compress
     * @param string|null $targetFile Optional target .gz file path (default: sourceFile.gz)
     * @param int $level Compression level 0-9 (default 9 = max)
     * 
     * @return string Path to the created gzip file
     */
    public function compress(string $sourceFile, ?string $targetFile = null, int $level = 9): string
    {
        if (!file_exists($sourceFile)) {
            throw new Exception("Source file not found: $sourceFile");
        }

        if ($targetFile === null) {
            $targetFile = $sourceFile . '.gz';
        }

        $in = fopen($sourceFile, 'rb');
        if ($in === false) {
            throw new Exception("Unable to open source file: $sourceFile");
        }

        $out = gzopen($targetFile, 'wb' . $level);
        if ($out === false) {
            fclose($in);
            throw new Exception("Unable to open target gzip file: $targetFile");
        }

        while (!feof($in)) {
            $data = fread($in, 1024 * 512);
            if ($data === false) {
                fclose($in);
                gzclose($out);
                throw new Exception("Error reading source file during compression.");
            }
            gzwrite($out, $data);
        }

        fclose($in);
        gzclose($out);

        @unlink($sourceFile);

        return $targetFile;
    }

    /**
     * Extract a gzip file to a target file
     * 
     * @param string $gzipFile Path to the .gz file
     * @param string|null $targetFile Optional target file path (default: gzipFile without .gz)
     * 
     * @return string Path to the extracted file
     */
    public function extract(string $gzipFile, ?string $targetFile = null): string
    {
        if (!file_exists($gzipFile)) {
            throw new Exception("GZip file not found: $gzipFile");
        }

        if ($targetFile === null) {
            $targetFile = preg_replace('/\.gz$/', '', $gzipFile);
        }

        $in = gzopen($gzipFile, 'rb');
        if ($in === false) {
            throw new Exception("Unable to open gzip file: $gzipFile");
        }

        $out = fopen($targetFile, 'wb');
        if ($out === false) {
            gzclose($in);
            throw new Exception("Unable to open target file: $targetFile");
        }

        while (!gzeof($in)) {
            $data = gzread($in, 1024 * 512);
            if ($data === false) {
                gzclose($in);
                fclose($out);
                throw new Exception("Error reading gzip file during extraction.");
            }
            fwrite($out, $data);
        }

        gzclose($in);
        fclose($out);

        @unlink($gzipFile);

        return $targetFile;
    }
}
