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

namespace App\Base\Abstracts\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;

abstract class BaseExecCommand extends BaseCommand
{
    public const RESULT_CODE = 'result_code';
    public const RESULT_OUTPUT = 'result_output';

    /**
     * Checks if command exists
     *
     * @param $cmd
     *
     * @return bool
     */
    protected function commandExist(string $cmd): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }

    protected function executeCommand(string $cmd, string $returnType = self::RESULT_CODE, bool $silent = false) : string|int
    {
        $code = Command::SUCCESS;
        
        $this->getIo()->writeln('Running <info>"'.$cmd.'"</info>');

/*
        ob_start();
        passthru($cmd, $code);
        $output = (string) ob_get_contents();
        ob_end_clean();
*/

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($cmd, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        
            $code = proc_close($process);        
        } else {
            throw new Exception("Errors during command \"$cmd\" execution!");            
        }

        if ($code != Command::SUCCESS) {
            throw new Exception("Errors during command \"$cmd\" execution [$code]: ".$err);
        }

        if ($returnType == self::RESULT_CODE && !$silent) {
            $this->getIo()->writeln($output);
        }

        return match($returnType) {
            self::RESULT_CODE => $code,
            self::RESULT_OUTPUT => $output,
            default => $code,
        };
    }
}