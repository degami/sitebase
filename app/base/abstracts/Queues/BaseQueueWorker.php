<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Abstracts\Queues;

use \App\Site\Models\QueueMessage;
use \App\Base\Abstracts\ContainerAwareObject;

/**
 * Base for queue workers
 */
abstract class BaseQueueWorker extends ContainerAwareObject
{
    /**
     * @var QueueMessage message to work
     */
    private $message;

    /**
     * process message
     *
     * @param QueueMessage $message
     * @return mixed
     */
    public function process(QueueMessage $message)
    {
        $this->setMessage($message);
        $result = $this->processMessage($this->getMessage()->getMessageData());
        $this->getMessage()->setResult(boolval($result) == true ? QueueMessage::ENDED_OK : QueueMessage::ENDED_KO)->persist();
        return $result;
    }

    /**
     * gets message
     *
     * @return QueueMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * set message
     *
     * @param QueueMessage $message
     * @return self
     */
    public function setMessage(QueueMessage $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * do message work phase
     *
     * @param QueueMessage $message_data
     * @return mixed
     */
    abstract protected function processMessage($message_data);
}
