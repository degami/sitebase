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

namespace App\Base\Cron\Tasks;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Models\ApplicationLog;
use App\App;
use DateTime;

/**
 * Logs manager cron
 */
class LoggerManager extends ContainerAwareObject
{
    public const DEFAULT_SCHEDULE = '0 0 1 * *';

    /**
     * rotates logger.log
     *
     * @return bool
     */
    public function truncateMonologLogs(): bool
    {
        $logDir = \App\App::getDir(\App\App::ROOT) . DS . 'var' . DS . 'log';
        $logFile = $logDir . DS . 'logger.log';
        $rotatedFile = $logDir . DS . 'logger.' . date('Ymd') . '.log';
        
        if (!file_exists($logFile)) {
            return true;
        }

        if (!@rename($logFile, $rotatedFile)) {
            if (!@copy($logFile, $rotatedFile)) {
                return false;
            }
            @unlink($logFile);
        }

        if (!@touch($logFile)) {
            return false;
        }

        @chmod($logFile, 0644);

        if (file_exists($rotatedFile)) {
            if (filesize($rotatedFile) === 0) {
                @unlink($rotatedFile);
            } else {
                $this->getGZip()->compress($rotatedFile);
            }
        }

        return true;
    }

    /**
     * archives application_log db entries to logfile
     *
     * @return bool
     */
    public function exportApplicationLogs(): bool
    {
        try {
            $logDir = App::getDir(App::ROOT) . DS . 'var' . DS . 'log';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $collection = ApplicationLog::getCollection();
            if ($collection->count() === 0) {
                return true;
            }

            $logFile = $logDir . DS . 'application_logs.' . date('Ymd') . '.log';
            $fp = fopen($logFile, 'a');
            if (!$fp) {
                throw new \RuntimeException("Impossibile aprire $logFile per scrittura");
            }

            foreach ($collection as $log) {
                /** @var ApplicationLog $log */

                $timestamp = (new DateTime((string)$log->getCreatedAt()))->format('Y-m-d H:i:s');
                $level = strtoupper($log->getLevel() ?? 'INFO');

                $messageParts = [];

                if ((int)$log->get('is_exception') === 1) {
                    $messageParts[] = "[exception] " . ($log->getExceptionMessage() ?: '(no message)');
                } else {
                    $messageParts[] = $log->getLogData() ?: '(no data)';
                }

                if ($log->getFile()) {
                    $messageParts[] = "in {$log->getFile()}:{$log->getLine()}";
                }

                $traceString = '';
                $trace = $log->getExceptionTrace();
                if ($trace && is_string($trace)) {
                    $decoded = json_decode($trace, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $traceLines = [];
                        $i = 0;
                        foreach ($decoded as $frame) {
                            $file = $frame['file'] ?? '[internal function]';
                            $line = isset($frame['line']) ? "({$frame['line']})" : '';
                            $function = $frame['function'] ?? '';
                            $traceLines[] = sprintf(
                                '#%d %s%s: %s()',
                                $i++,
                                $file,
                                $line,
                                $function
                            );
                        }
                        $traceLines[] = sprintf('#%d {main}', $i);
                        $traceString = implode("\n", $traceLines);
                    }
                }

                $message = implode(' | ', $messageParts);
                if ($traceString) {
                    $message .= "\n" . $traceString;
                }

                $logLine = sprintf("[%s] applicationlog.%s: %s\n", $timestamp, $level, $message);
                fwrite($fp, $logLine);

                $log->delete();
            }

            fclose($fp);

            if (file_exists($logFile)) {
                if (filesize($logFile) === 0) {
                    @unlink($logFile);
                } else {
                    $this->getGZip()->compress($logFile);
                }
            }

            return true;
        } catch (\Throwable $e) {
            $this->getLog()->error("Errore nell'esportazione applicationlog: " . $e->getMessage());
            return false;
        }
    }    
}
