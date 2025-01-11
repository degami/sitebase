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

namespace App\Base\Abstracts\Queues;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Interfaces\Queue\QueueMessageInterface;
use App\Base\Interfaces\Queue\QueueWorkerInterface;
use Degami\Basics\Exceptions\BasicException;

/**
 * Base for queue workers
 */
abstract class BaseQueueWorker extends ContainerAwareObject implements QueueWorkerInterface
{
    /**
     * @var QueueMessageInterface message to work
     */
    private QueueMessageInterface $message;

    /**
     * process message
     *
     * @param QueueMessageInterface $message
     * @return mixed
     * @throws BasicException
     */
    public function process(QueueMessageInterface $message): mixed
    {
        $this->setMessage($message);
        $result = $this->processMessage($this->getMessage()->getMessageData());
        $this->getMessage()->setResult(boolval($result) == true ? QueueMessageInterface::ENDED_OK : QueueMessageInterface::ENDED_KO)->persist();
        return $result;
    }

    /**
     * gets message
     *
     * @return QueueMessageInterface
     */
    public function getMessage(): QueueMessageInterface
    {
        return $this->message;
    }

    /**
     * set message
     *
     * @param QueueMessageInterface $message
     * @return self
     */
    public function setMessage(QueueMessageInterface $message): BaseQueueWorker
    {
        $this->message = $message;

        return $this;
    }
}
