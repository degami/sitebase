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
namespace App\Site\Queues\ContactFormMail;

use \Psr\Container\ContainerInterface;
use \App\Site\Models\QueueMessage;
use \App\Base\Abstracts\BaseQueueWorker;

/**
 * Contacts Form Queue Worker
 */
class Worker extends BaseQueueWorker
{
    /**
     * {@inheritdocs}
     * @param  array $message_data
     * @return boolean
     */
    protected function processMessage($message_data)
    {
        return $this->getMailer()->sendMail(
            $message_data['from'],
            $message_data['to'],
            $message_data['subject'],
            $message_data['body']
        );
    }
}
