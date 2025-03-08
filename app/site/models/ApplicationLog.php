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

namespace App\Site\Models;

use DateTime;
use Exception;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;
use App\Base\Abstracts\Models\BaseModel;
use Error;
use Throwable;

/**
 * Application Log Model
 *
 * @method int getId()
 * @method string getUserId()
 * @method string getIpAddress()
 * @method string getFile()
 * @method int getLine()
 * @method string getLogData()
 * @method string getLevel()
 * @method bool getIsException()
 * @method string getExceptionMessage()
 * @method string getExceptionTrace()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setUserId(string $user_id)
 * @method self setIpAddress(string $ip_address)
 * @method self setFile(string $file)
 * @method self setLine(int $line)
 * @method self setLogData(string $log_data)
 * @method self setLevel(string $level)
 * @method self setIsException(bool $is_exception)
 * @method self setExceptionMessage(string $exception_message)
 * @method self setExceptionTrace(string $exception_trace)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class ApplicationLog extends BaseModel
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    /**
     * fills log with exception
     *
     * @param Throwable $e
     * @return $this|ApplicationLog
     */
    public function fillWithException(Throwable|Exception|Error $e) : static
    {
        $this->setIsException(true);
        $this->setLevel(static::LEVEL_CRITICAL);
        $this->setExceptionMessage($e->getMessage());
        $this->setFile($e->getFile());
        $this->setLine($e->getLine());
        $traceInfo = [];

        foreach ($e->getTrace() as $frame) {
            $functionDetails = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function']
            ];
            
            if (isset($frame['class'])) {
                $functionDetails['function'] = $frame['class'] . $frame['type'] . $frame['function'];
            }
            
            $reflection = null;
            try {
                if (isset($frame['class'])) {
                    $reflection = new ReflectionMethod($frame['class'], $frame['function']);
                } else {
                    $reflection = new ReflectionFunction($frame['function']);
                }
            } catch (ReflectionException $e) {
                $reflection = null;
            }
            
            if ($reflection) {
                $params = [];
                foreach ($reflection->getParameters() as $param) {
                    $type = $param->hasType() ? $param->getType() : 'mixed';
                    $params[] = ['name' => $param->getName(), 'type' => $type];
                }
                $functionDetails['parameters'] = $params;
            }
            
            $traceInfo[] = $functionDetails;
        }

        $this->setExceptionTrace(json_encode($traceInfo));
        return $this;
    }
}
