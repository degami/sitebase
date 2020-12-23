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

namespace App\Site\Queues\LinkFormMail;

use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \App\Base\Abstracts\Queues\BaseQueueWorker;

/**
 * Links Form Queue Worker
 */
class Worker extends BaseQueueWorker
{
    /**
     * {@inheritdocs}
     *
     * @param array $message_data
     * @return boolean
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function processMessage(array $message_data): bool
    {
        return $this->getMailer()->sendMail(
            $message_data['from'],
            $message_data['to'],
            $message_data['subject'],
            $message_data['body']
        );
    }
}
