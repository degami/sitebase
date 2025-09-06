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

    public function start() : self
    {
        return $this->setProgress(0)->setStartedAt(date('Y-m-d H:i:s'));
    }

    public function end(?int $exitStatus = self::SUCCESS) : self
    {
        if (!$this->getStartedAt()) {
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

    public function success() : self
    {
        return $this->end(self::SUCCESS);
    }

    public function failure() : self
    {
        return $this->end(self::FAILURE);
    }

    public function getProgressPercentual() : ?float
    {
        if (!$this->getTotal()) {
            return null;
        }

        return floatval($this->getProgress() / $this->getTotal() * 100);
    }

    public function increment(int $step = 1) : self
    {
        return $this->setProgress($this->getProgress() + $step);
    }

    public function progress(int $step = 1) : self
    {
        return $this->increment($step)->setMessage('progress '. (!is_null($this->getProgressPercentual()) ? round($this->getProgressPercentual(), 2).'%' : 'n/a'));
    }

    public function isStarted() : bool
    {
        return $this->getStartedAt() != null;
    }

    public function isEnded() : bool
    {
        return $this->getEndedAt() != null;
    }

    public function isRunning() : bool
    {
        return $this->isStarted() && !$this->isEnded();
    }

    public function run(...$args) : self
    {
        if ($this->isStarted()) {
            throw new Exception('Process '.$this->getId().' is already running');
        }

        if ($this->isEnded()) {
            throw new Exception('Process '.$this->getId().' has already been run');
        }

        $pid = getmypid();

        try {
            $callable = json_decode($this->getCallable(), true);
            if (!$callable || !is_callable($callable)) {
                return $this->end(self::INVALID)->persist();
            }

            $this->setPid($pid)->start()->persist();

            App::getInstance()->containerCall($callable, array_merge([$this], $args));

            $this->setMessage('Run complete.')->success();
        } catch (Exception $e) {
            $this->setMessage($e->getMessage())->failure();
        }

        return $this->persist();
    }

    public function abort() : self
    {
        $pid = $this->getPid();
        if ($pid) {
            posix_kill($pid, 15);
        }
        return $this->setExitStatus(self::ABORT)->setEndedAt(date('Y-m-d H:i:s'))->persist();
    }
}
