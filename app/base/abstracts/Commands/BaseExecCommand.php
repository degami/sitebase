<?php


namespace App\Base\Abstracts\Commands;


abstract class BaseExecCommand extends BaseCommand
{
    /**
     * Checks if command exists
     *
     * @param $cmd
     *
     * @return bool
     */
    protected function commandExist($cmd): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }
}