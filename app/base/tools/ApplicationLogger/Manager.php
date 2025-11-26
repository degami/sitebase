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

namespace App\Base\Tools\ApplicationLogger;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Models\ApplicationLog;
use Error;
use Exception;
use Throwable;

/**
 * Application Logger Manager
 * 
 * This class manages application logs and provides different logging levels.
 */
class Manager extends ContainerAwareObject
{
    /**
     * Logs a message at the specified level.
     *
     * @param string $message     The log message.
     * @param string $level       The log level.
     * @param int    $callerLevel The stack trace level to fetch caller info. not intended for direct/external usage
     *
     * @return ApplicationLog|null The created log entry, or null on failure.
     */
    public function log(string $message, string $level, int $callerLevel = 2) : ?ApplicationLog
    {
        try {
            /** @var ApplicationLog $log */
            $log = $this->containerMake(ApplicationLog::class);

            $callerInfo = $this->getCaller($callerLevel);

            // as log_data field is a "text" type, we limit the message length to 65535 bytes. If longer, we truncate it.
            // as we do not know the encoding, we will reduce it to 2/3 of that size to be sure we do not cut a multibyte char

            if (strlen($message) > 43690) {
                $message = substr($message, 0, 43690) . "\n...[truncated]";
            }

            $log->setFile($callerInfo['file'])
                ->setLine($callerInfo['line'])
                ->setLogData($message)
                ->setLevel($level);

            if ($this->getEnvironment()->isWeb()) {
                $log->setIpAddress($this->getEnvironment()->getRequest()?->getClientIp());
            }

            if ($this->getAuth()->getCurrentUser()) {
                $log->setUserId($this->getAuth()->getCurrentUser()->getId());
            }

            $log->persist();

            return $log;
        } catch (Exception $e) {
            $this->getUtils()->logException($e, "Can't write ApplicationLog");
        }

        return null;
    }

    /**
     * Logs a debug message.
     *
     * @param string $message The debug message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function debug(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_DEBUG, 3);
    }

    /**
     * Logs an info message.
     *
     * @param string $message The info message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function info(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_INFO, 3);
    }

    /**
     * Logs a notice message.
     *
     * @param string $message The notice message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function notice(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_NOTICE, 3);
    }

    /**
     * Logs a warning message.
     *
     * @param string $message The warning message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function warning(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_WARNING, 3);
    }

    /**
     * Logs an error message.
     *
     * @param string $message The error message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function error(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_ERROR, 3);
    }    

    /**
     * Logs a critical error message.
     *
     * @param string $message The critical error message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function critical(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_CRITICAL, 3);
    }

    /**
     * Logs an alert message.
     *
     * @param string $message The alert message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function alert(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_ALERT, 3);
    }

    /**
     * Logs an emergency message.
     *
     * @param string $message The emergency message.
     *
     * @return ApplicationLog|null The created log entry.
     */
    public function emergency(string $message) : ?ApplicationLog
    {
        return $this->log($message, ApplicationLog::LEVEL_EMERGENCY, 3);
    }

    /**
     * Logs an exception with details.
     *
     * @param Throwable|Exception|Error $e The exception to log.
     *
     * @return ApplicationLog|null The created log entry, or null on failure.
     */
    public function exception(Throwable|Exception|Error $e) : ?ApplicationLog
    {
        try {
            /** @var ApplicationLog $log */
            $log = $this->containerMake(ApplicationLog::class);
            $log->fillWithException($e);
            $log->persist();

            return $log;
        } catch (Exception $e) {
            $this->getUtils()->logException($e, "Can't write ApplicationLog");
            return null;
        }
    }

    /**
     * Retrieves the caller's file and line from the backtrace.
     *
     * @param int $level The backtrace level to inspect.
     *
     * @return array<string, mixed> An array containing 'file' and 'line' keys.
     */
    protected function getCaller(int $level = 2): array 
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level + 1);
        
        if (isset($trace[$level])) {
            return [
                'file' => $trace[$level]['file'] ?? 'unknown',
                'line' => $trace[$level]['line'] ?? 0
            ];
        }
        
        return ['file' => 'unknown', 'line' => 0];
    }
}
