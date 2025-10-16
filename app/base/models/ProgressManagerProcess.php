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

namespace App\Base\Models;

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use DateTime;
use Exception;
use InvalidArgumentException;
use Reflection;
use ReflectionFunction;
use ReflectionMethod;

/**
 * ProgressManager Process Model
 *
 * @method int getId()
 * @method int getPid()
 * @method string getCallable()
 * @method int getTotal()
 * @method int getProgress()
 * @method string getMessage()
 * @method DateTime getStartedAt()
 * @method DateTime getEndedAt()
 * @method int getExitStatus()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setPid(int $pid)
 * @method self setCallable(string $callable)
 * @method self setTotal(int $total)
 * @method self setProgress(int $progress)
 * @method self setMessage(string $message)
 * @method self setExitStatus(int $exitStatus)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class ProgressManagerProcess extends BaseModel
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;
    public const ABORT = 3;

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * Starts current process
     * 
     * @return self
     */
    public function start() : self
    {
        return $this->setProgress(0)->setStartedAt(date('Y-m-d H:i:s'));
    }

    /**
     * Ends current process, setting an exist status
     * 
     * @param int|null $exitStatus
     * @return self
     */
    public function end(?int $exitStatus = self::SUCCESS) : self
    {
        if (!in_array($exitStatus, [self::INVALID]) && !$this->getStartedAt()) {
            throw new Exception('Process not started');
        }

        if (!$this->getTotal()) {
            $this->setTotal($this->getProgress());
        }

        if ($exitStatus != self::SUCCESS) {
            return $this->setExitStatus($exitStatus)->setEndedAt(date('Y-m-d H:i:s'));            
        }

        return $this->setProgress($this->getTotal())->setExitStatus($exitStatus)->setEndedAt(date('Y-m-d H:i:s'));
    }

    /**
     * Sets current process as successful
     * 
     * @return self
     */
    public function success() : self
    {
        return $this->end(self::SUCCESS);
    }

    /**
     * Sets current process as failed
     * 
     * @return self
     */
    public function failure() : self
    {
        return $this->end(self::FAILURE);
    }

    /**
     * Calculates current progress percentual
     * 
     * @return float|null
     */
    public function getProgressPercentual() : ?float
    {
        if (!$this->getTotal()) {
            return null;
        }

        return floatval($this->getProgress() / $this->getTotal() * 100);
    }

    /**
     * Advance progress counter
     * 
     * @param int $step
     * @return self
     */
    public function increment(int $step = 1) : self
    {
        return $this->setProgress($this->getProgress() + $step);
    }

    /**
     * Advance progress counter, updating message with current percentual
     * 
     * @param int $step
     * @return self
     */
    public function progress(int $step = 1) : self
    {
        return $this->increment($step)->setMessage('progress '. (!is_null($this->getProgressPercentual()) ? round($this->getProgressPercentual(), 2).'%' : 'n/a'));
    }

    /**
     * Check if process is started
     * 
     * @return bool
     */
    public function isStarted() : bool
    {
        return $this->getStartedAt() != null;
    }

    /**
     * Check if process is ended
     * 
     * @return bool
     */
    public function isEnded() : bool
    {
        return $this->getEndedAt() != null;
    }

    /**
     * Check if process is running
     * 
     * @return bool
     */
    public function isRunning() : bool
    {
        return $this->isStarted() && !$this->isEnded();
    }

    /**
     * Runs callable with arguments. Injects process instance as first parameter to keep track of process status
     * 
     * @return mixed
     */
    public function run(...$args) : mixed
    {
        if ($this->isStarted()) {
            throw new Exception('Process '.$this->getId().' is already running');
        }

        if ($this->isEnded()) {
            throw new Exception('Process '.$this->getId().' has already been run');
        }

        $pid = getmypid();

        $result = null;
        try {
            $callable = json_decode($this->getCallable(), true);
            if (!$callable || !is_callable($callable)) {
                return $this->invalid();
            }

            $reflection = new ReflectionMethod(...$callable);
            $parameters = $reflection->getParameters();

            // check if first parameter in callable is a ProgressManagerProcess
            if (!$parameters[0]->getType()?->getName() === ProgressManagerProcess::class) {
                throw new Exception('First parameter of callable must be a ProgressManagerProcess instance');
            }

            $this->setPid($pid)->start()->persist();

            $result = App::getInstance()->containerCall($callable, array_merge([$this], $args));

            $this->setMessage('Run complete.')->success();
        } catch (Exception $e) {
            $this->setMessage($e->getMessage())->failure();
        }

        $this->persist();

        return $result;
    }

    /**
     * Aborts current process, killing pid if available
     * 
     * @return self
     */
    public function abort() : self
    {
        $pid = $this->getPid();
        if ($pid) {
            posix_kill($pid, 15);
        }
        return $this->setExitStatus(self::ABORT)->setEndedAt(date('Y-m-d H:i:s'))->persist();
    }

    /**
     * Sets current process as invalid
     * 
     * @return self
     */
    public function invalid() : self
    {
        return $this->setExitStatus(self::INVALID)->setEndedAt(date('Y-m-d H:i:s'))->persist();
    }

    /**
     * Utility function to create a ProgressManagerProcess for a callable
     * 
     * @param string|array $callable
     * @return ProgressManagerProcess
     */
    public static function createForCallable(string|array $callable) : ProgressManagerProcess
    {
        $out = new self();
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Invalid callable argument');
        }

        $out->setCallable(json_encode($callable));
        return $out;
    }
}
