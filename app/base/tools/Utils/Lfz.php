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
 * Lzf utils functions Helper Class
 */
class Lzf extends ContainerAwareObject
{
    /**
     * Compress a single file into lzf format
     * 
     * @param string $sourceFile      Path to the file to compress
     * @param string|null $targetFile Optional target .lzf file path (default: sourceFile.lzf)
     * @param int $level Compression level 0-9 (default 9 = max)
     * 
     * @return string Path to the created lzf file
     */
    public function compress(string $sourceFile, ?string $targetFile = null, int $level = 9): string
    {
        if (!function_exists('lfz_compress')) {
            throw new Exception("LZF functions not found. Please enable lzf extension.");
        }

        if (!file_exists($sourceFile)) {
            throw new Exception("Source file not found: $sourceFile");
        }

        if ($targetFile === null) {
            $targetFile = $sourceFile . '.lzf';
        }

        $in = fopen($sourceFile, 'rb');
        if ($in === false) {
            throw new Exception("Unable to open source file: $sourceFile");
        }

        $out = fopen($targetFile, 'wb');
        if ($out === false) {
            fclose($in);
            throw new Exception("Unable to open target lzf file: $targetFile");
        }

        while (!feof($in)) {
            $data = fread($in, 1024 * 512);
            if ($data === false) {
                fclose($in);
                fclose($out);
                throw new Exception("Error reading source file during compression.");
            }
            fwrite($out, \lzf_compress($data));
        }

        fclose($in);
        fclose($out);

        @unlink($sourceFile);

        return $targetFile;
    }

    /**
     * Extract a lzf file to a target file
     * 
     * @param string $lzfFile Path to the .lzf file
     * @param string|null $targetFile Optional target file path (default: lzfFile without .lzf)
     * 
     * @return string Path to the extracted file
     */
    public function extract(string $lzfFile, ?string $targetFile = null): string
    {
        if (!function_exists('lzf_decompress')) {
            throw new Exception("LZF functions not found. Please enable lzf extension.");
        }

        if (!file_exists($lzfFile)) {
            throw new Exception("LZF file not found: $lzfFile");
        }

        if ($targetFile === null) {
            $targetFile = preg_replace('/\.lzf$/', '', $lzfFile);
        }

        $in = fopen($lzfFile, 'rb');
        if ($in === false) {
            throw new Exception("Unable to open lzf file: $lzfFile");
        }

        $out = fopen($targetFile, 'wb');
        if ($out === false) {
            fclose($in);
            throw new Exception("Unable to open target file: $targetFile");
        }

        while (!feof($in)) {
            $data = fread($in, 1024 * 512);
            if ($data === false) {
                fclose($in);
                fclose($out);
                throw new Exception("Error reading lzf file during extraction.");
            }
            fwrite($out, \lzf_decompress($data));
        }

        fclose($in);
        fclose($out);

        @unlink($lzfFile);

        return $targetFile;
    }
}
